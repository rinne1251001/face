<?php

namespace App\Http\Controllers;

use App\Models\FaceSample;
use App\Models\Person;
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

// 顔写真（サンプルデータ）の新規登録・全件一括トランザクション保存・画像の表示取得・削除・キャッシュ破棄を行うクラス
class FaceSampleController extends Controller
{
    // FaceMatcherクラスのオブジェクトを受け取り、$this->matcherという変数に代入
    public function __construct(private FaceMatcher $matcher) {}

    // faces/register.blade.php（顔の登録画面）を表示
    public function create(Person $person): View
    {
        return view('faces.register', compact('person'));
    }

    // クライアントから送信された複数の顔サンプルデータを保存
    public function store(Request $request, Person $person): JsonResponse
    {
        // バリデーションチェック
        $data = $request->validate([
            'model_version' => ['required', 'string', Rule::in([config('face.model_version')])],
            'samples' => ['required', 'array', 'min:1', 'max:'.config('face.max_samples_per_request')],
            'samples.*.embedding' => ['required', 'array'],
            'samples.*.image' => ['required', 'string', 'max:3000000'],
        ], ['model_version.in' => 'モデルのバージョンがサーバー設定と一致しません。']);

        /**
         * データベースやファイルシステムに保存するための「綺麗で安全なデータ」に変換
         * すべて検証してから保存する
         */ 
        $prepared = [];
        foreach ($data['samples'] as $i => $sample) {
            $prepared[] = [
                // FaceMatcherクラスのtoUnitVectorメソッドで長さを1にする
                'vector' => FaceMatcher::toUnitVector($sample['embedding'], "samples.$i.embedding"),
                // Base64形式の画像文字列を本物のJPEGバイナリデータに変換し、不正なデータはエラーを投げる
                'jpeg' => $this->decodeJpeg($sample['image'], "samples.$i.image"),
            ];
        }

        // 顔画像をデータベースに保存
        $paths = [];
        try {
            DB::transaction(function () use ($prepared, $person, $data, &$paths) {
                foreach ($prepared as $p) {
                    $path = "faces/{$person->id}/".Str::uuid().'.jpg';
                    Storage::disk('local')->put($path, $p['jpeg']);
                    $paths[] = $path;

                    $person->faceSamples()->create([
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

        // 新しい顔写真が追加されたのでFaceMatcherの古いキャッシュを削除
        $this->matcher->flush();

        // JSON形式でクライアントへ返却
        return response()->json([
            // 今回保存した件数
            'saved' => count($prepared),
            // その人物の現在の画像登録総数
            'total' => $person->faceSamples()->count(),
        ]);
    }

    // 顔写真画像を取得・表示するメソッド
    public function image(FaceSample $faceSample): StreamedResponse
    {
        // DBにパスが存在しないorファイル自体がストレージ上に存在しない場合はHTTP404を出力し中断
        abort_unless($faceSample->image_path && Storage::disk('local')->exists($faceSample->image_path), 404);

        // セキュリティ保護のため、ブラウザや中間キャッシュに画像をキャッシュさせないレスポンスヘッダーを付与してレスポンス返却
        return Storage::disk('local')->response($faceSample->image_path, null, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    // 指定された顔データを1件削除するメソッド
    public function destroy(FaceSample $faceSample): RedirectResponse
    {
        // ストレージから物理ファイルを削除
        if ($faceSample->image_path) {
            Storage::disk('local')->delete($faceSample->image_path);
        }
        // データベースから FaceSample レコードを削除
        $faceSample->delete();
        // 顔写真が削除されたのでFaceMatcherの古いキャッシュを削除
        $this->matcher->flush();

        return back()->with('status', '顔写真を1枚削除しました。');
    }

    // Data URL形式（テキスト化した画像データ）をJPEGバイナリ（本物のJPEG画像データ）へ変換する非公開メソッド
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