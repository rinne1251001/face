<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardRead;
use App\Models\Student;
use App\Models\StudentCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// 入校証（FeliCa）の照合と、持ち主の登録（管理者）
// 入校証を読み取って IDm に合う生徒を表示し、まだ誰のものでもない入校証なら、生徒を選んで登録する
// ※ 入校証の一覧・編集・削除（CRUD）は、まだ作っていない
class CardMatchController extends Controller
{
    // IDm のバリデーション（16桁の16進数）
    private const IDM_RULE = ['required', 'string', 'regex:'.StudentCard::IDM_PATTERN];

    private const IDM_MESSAGES = [
        'idm.required' => 'IDm が送信されていません。',
        'idm.regex' => 'IDm の形式が正しくありません（16桁の16進数である必要があります）。',
    ];

    // 照合・登録の画面
    public function show(): View
    {
        return view('admin.cards.match');
    }

    // タッチされた入校証を照合する（はじめての入校証は、誰のものでもない入校証として自動で登録する）
    public function touch(Request $request): JsonResponse
    {
        $validated = $request->validate(['idm' => self::IDM_RULE], self::IDM_MESSAGES);
        $idm = (string) StudentCard::normalizeIdm($validated['idm']);

        $isNew = ! StudentCard::where('idm', $idm)->exists();

        // タッチの記録を残す（はじめての入校証なら、ここで入校証も登録される）
        $card = CardRead::record($idm)->card()->with('student.currentEnrollment.schoolClass')->firstOrFail();

        if (! $card->is_active) {
            return response()->json([
                'status' => 'inactive',
                'idm' => $idm,
                'message' => '使用停止中の入校証です。',
            ]);
        }

        if ($card->student === null) {
            return response()->json([
                'status' => 'unlinked',
                'idm' => $idm,
                'message' => $isNew
                    ? "はじめて読み取った入校証です（IDm {$idm}）。"
                    : "生徒が登録されていない入校証です（IDm {$idm}）。",
            ]);
        }

        return response()->json([
            'status' => 'linked',
            'idm' => $idm,
            'student' => $this->studentData($card->student),
        ]);
    }

    // 名前・フリガナ・学籍番号で生徒を探す（入校証に登録する生徒を選ぶため）
    // 生徒の名前を URL やアクセスログに残さないよう、検索語は POST の本文で受け取る
    public function students(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'max:50']]);
        $keyword = trim($validated['q']);

        // フリガナは「ひらがな」「カタカナ」どちらで保存されていても見つかるようにする
        // （% と _ はワイルドカードとして扱われるが、検索結果が広がるだけなので問題ない）
        $hiragana = mb_convert_kana($keyword, 'cHV');
        $katakana = mb_convert_kana($keyword, 'CKV');

        $students = Student::query()
            ->with('currentEnrollment.schoolClass')
            // 使用中の入校証をすでに持っているか（has_card）
            ->withExists(['cards as has_card' => fn ($card) => $card->where('is_active', true)])
            ->where('is_active', true) // 卒業・退学などで停止中の生徒は選べない
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('furigana', 'like', "%{$hiragana}%")
                ->orWhere('furigana', 'like', "%{$katakana}%")
                ->orWhere('student_number', 'like', "{$keyword}%"))
            ->orderBy('student_number')
            ->limit(20)
            ->get();

        return response()->json([
            'students' => $students->map(fn (Student $student) => $this->studentData($student)
                + ['has_card' => (bool) $student->getAttribute('has_card')])->values(),
        ]);
    }

    // 生徒が決まっていない入校証に、生徒を登録する
    public function link(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'idm' => self::IDM_RULE,
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ], self::IDM_MESSAGES + [
            'student_id.required' => '生徒を選んでください。',
            'student_id.exists' => '選んだ生徒が見つかりません。',
        ]);
        $idm = (string) StudentCard::normalizeIdm($validated['idm']);

        $card = StudentCard::where('idm', $idm)->first();
        if ($card === null) {
            return response()->json(['message' => 'この入校証はまだ読み取られていません。もう一度タッチしてください。'], 404);
        }
        if (! $card->is_active) {
            return response()->json(['message' => '使用停止中の入校証には生徒を登録できません。'], 422);
        }

        $student = Student::with('currentEnrollment.schoolClass')->findOrFail((int) $validated['student_id']);
        if (! $student->is_active) {
            return response()->json(['message' => '停止中の生徒は登録できません。'], 422);
        }

        $linked = DB::transaction(function () use ($card, $student) {
            // 生徒が決まっていない場合だけ書き換える（同時に2人が登録しようとしても、先に登録した方だけが残る）
            $updated = StudentCard::whereKey($card->id)
                ->whereNull('student_id')
                ->update(['student_id' => $student->id]);
            if ($updated === 0) {
                return false;
            }

            // 登録の直前（5分以内）にこの入校証をタッチした記録にも、生徒を付けておく
            $card->reads()
                ->whereNull('student_id')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->update(['student_id' => $student->id]);

            return true;
        });

        if (! $linked) {
            return response()->json(['message' => 'この入校証には、すでに生徒が登録されています。'], 409);
        }

        return response()->json([
            'status' => 'linked',
            'idm' => $idm,
            'student' => $this->studentData($student),
            'message' => "{$student->name}（{$student->student_number}）さんの入校証として登録しました。",
        ]);
    }

    // 画面に返す生徒の情報（名前・学籍番号・今年度のクラス・停止中かどうか）
    /** @return array{id: int, name: string, student_number: string, class_name: string|null, is_active: bool} */
    private function studentData(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'student_number' => $student->student_number,
            'class_name' => $student->currentEnrollment?->schoolClass?->name,
            'is_active' => $student->is_active,
        ];
    }
}