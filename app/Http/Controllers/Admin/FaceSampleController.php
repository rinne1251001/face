<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaceSample;
use App\Models\Student;
use App\Services\FaceMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

// 顔写真（サンプルデータ）の登録・画像の表示・削除（管理者のみ）
class FaceSampleController extends Controller
{
    public function __construct(private FaceMatcher $matcher) {}

    // admin/faces/register.blade.php（顔の登録画面）を表示
    public function create(Student $student): View
    {
        return view('admin.faces.register', compact('student'));
    }

    // クライアントから送信された複数の顔サンプルデータを保存
    public function store(Request $request, Student $student): JsonResponse
    {
        $data = $request->validate([
            'model_version' => ['required', 'string', Rule::in([config('face.model_version')])],
            'samples' => ['required', 'array', 'min:1', 'max:'.config('face.max_samples_per_request')],
            'samples.*.embedding' => ['required', 'array'],
            'samples.*.image' => ['required', 'string', 'max:3000000'],
        ], ['model_version.in' => 'モデルのバージョンがサーバー設定と一致しません。']);

        // すべて検証してから保存する
        $prepared = [];
        foreach ($data['samples'] as $i => $sample) {
            $prepared[] = [
                'vector' => FaceMatcher::toUnitVector($sample['embedding'], "samples.$i.embedding"),
                'jpeg' => $this->decodeJpeg($sample['image'], "samples.$i.image"),
            ];
        }

        $paths = [];
        try {
            DB::transaction(function () use ($prepared, $student, $data, &$paths) {
                foreach ($prepared as $p) {
                    $path = "faces/{$student->id}/".Str::uuid().'.jpg';
                    Storage::disk('local')->put($path, $p['jpeg']);
                    $paths[] = $path;

                    $student->faceSamples()->create([
                        'image_path' => $path,
                        'embedding' => $p['vector'],
                        'dimension' => count($p['vector']),
                        'model_version' => $data['model_version'],
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($paths); // DB保存に失敗したら画像も消す
            throw $e;
        }

        $this->matcher->flush();

        return response()->json([
            'saved' => count($prepared),
            'total' => $student->faceSamples()->count(),
        ]);
    }

    // 顔写真画像を表示する（ログイン中の管理者だけが見られる）
    public function image(FaceSample $faceSample): StreamedResponse
    {
        abort_unless($faceSample->image_path && Storage::disk('local')->exists($faceSample->image_path), 404);

        return Storage::disk('local')->response($faceSample->image_path, null, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    // 顔写真を1枚削除する
    public function destroy(FaceSample $faceSample): RedirectResponse
    {
        if ($faceSample->image_path) {
            Storage::disk('local')->delete($faceSample->image_path);
        }
        $faceSample->delete();
        $this->matcher->flush();

        return back()->with('status', '顔写真を1枚削除しました。');
    }

    // Data URL 形式の画像を JPEG のバイナリに変換する（不正なデータはエラー）
    private function decodeJpeg(string $dataUrl, string $field): string
    {
        $prefix = 'data:image/jpeg;base64,';
        $binary = str_starts_with($dataUrl, $prefix)
            ? base64_decode(substr($dataUrl, strlen($prefix)), true)
            : false;

        if ($binary === false || @getimagesizefromstring($binary) === false) {
            throw ValidationException::withMessages([$field => '画像データが正しくありません。']);
        }

        return $binary;
    }
}
