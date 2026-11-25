<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AcademicYear;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

// 年度を扱うコントローラーの共通処理（生徒・クラス・授業・教師・スケジュール・出席で使う）
// 使い方：コントローラーで use UsesAcademicYear; と書いて、$this->yearOrFail($request) などを呼ぶ
trait UsesAcademicYear
{
    // ?year=2026（フォームでは hidden の year）の年度。指定がなければ今年度（今年度がなければ、いちばん新しい年度）
    // 年度が1つもないときは null
    protected function findYear(Request $request): ?AcademicYear
    {
        return $request->filled('year')
            ? AcademicYear::query()->where('year', $request->integer('year'))->first()
            : AcademicYear::currentOrNewest();
    }

    // findYear() と同じだが、年度が見つからなければ 404 にする
    protected function yearOrFail(Request $request): AcademicYear
    {
        $year = $this->findYear($request);
        abort_if($year === null, 404, '年度が見つかりません。');

        return $year;
    }

    // 読み取り専用の年度なら、変更させない
    // （画面ではボタンを出していないが、開いたままの古い画面から送られた場合などのため）
    protected function ensureEditable(AcademicYear $year): void
    {
        abort_unless($year->isEditable(), 403,
            "{$year->label}は読み取り専用のため、変更できません。変更が必要なときは、管理者が年度の画面で編集を許可してください。");
    }

    // その日を含む年度が読み取り専用なら、変更させない（休校・振替・授業変更など、日付で決まるもの）
    // どの年度にも入らない日は、制限しない
    protected function ensureDateEditable(CarbonInterface|string $date): void
    {
        $year = AcademicYear::containing($date instanceof CarbonInterface ? $date : Carbon::parse($date));
        if ($year !== null) {
            $this->ensureEditable($year);
        }
    }
}