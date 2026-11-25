<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// 出席率の集計（教師の「分析」の画面で使う）
//
// 数え方（学校のルールに合わせるときは、このクラスだけ直せばよい）
//   ・数えるのは「授業が行われたコマ」だけ。出欠の記録が1件以上あるコマを、行われたコマとみなす
//     （祝日・休講のコマや、まだ出欠を取っていないコマを、全員欠席として数えないため）
//   ・行われたコマで、受けるはずの生徒に記録がなければ「欠席」として数える（顔認証をしなかった人は欠席）
//     ただし、受付がまだ終わっていないコマ（今日のこれからの授業）は、記録がある生徒だけを数える
//     （受付の時間は CheckinWindow。顔認証の受付では、来た生徒の分しか記録が作られないため）
//     受けるはずの生徒 ＝ 授業の受講生（クラスの授業変更で、そのコマを受けないクラスの生徒は除く。クラス未定の生徒は含める）
//   ・出席率 ＝（出席 ＋ 遅刻）÷（コマ数 − 公欠）。公欠は、出席にも欠席にも数えない
//
// 1件の「出欠」は、1人 × 1授業 × 1日 × 1時限。配列で持つ：
//   ['student_id' => 1, 'subject_id' => 2, 'date' => '2026-10-05', 'month' => '2026-10', 'weekday' => 1, 'period' => 1, 'status' => 'present']
class AttendanceStats
{
    // 目立たせる基準：出席率がこれより低い（%）
    public const LOW_RATE = 70;

    // 目立たせる基準：前の月よりこれ以上下がった（ポイント）
    public const DROP = 10;

    /** @var array<int, Collection<int, array<string, mixed>>> 授業ID => その授業の出欠（同じ画面の中で何度も計算しないように覚えておく） */
    private array $cache = [];

    public function __construct(private SchoolCalendar $calendar) {}

    // ---------- 出欠を集める ----------

    // ある授業の出欠（その授業の年度の初めから、今日まで）
    /** @return Collection<int, array<string, mixed>> */
    public function forSubject(Subject $subject): Collection
    {
        return $this->cache[$subject->id] ??= $this->build($subject);
    }

    // あるクラスの生徒の出欠（そのクラスの年度の、すべての授業）
    /** @return Collection<int, array<string, mixed>> */
    public function forClass(SchoolClass $class): Collection
    {
        $studentIds = $class->enrollments()->pluck('student_id')->all();

        // このクラスの生徒が受けている、その年度の授業
        $subjects = Subject::query()
            ->where('academic_year_id', $class->academic_year_id)
            ->whereHas('students', fn ($q) => $q->whereIn('students.id', $studentIds))
            ->get();

        return $this->forSubjects($subjects)->whereIn('student_id', $studentIds)->values();
    }

    // ある生徒の出欠（その年度の、受けているすべての授業）
    /** @return Collection<int, array<string, mixed>> */
    public function forStudent(Student $student, AcademicYear $year): Collection
    {
        $subjects = $student->subjects()->where('subjects.academic_year_id', $year->id)->get();

        return $this->forSubjects($subjects)->where('student_id', $student->id)->values();
    }

    // いくつかの授業の出欠をまとめたもの
    /**
     * @param  iterable<Subject>  $subjects
     * @return Collection<int, array<string, mixed>>
     */
    public function forSubjects(iterable $subjects): Collection
    {
        $all = collect();
        foreach ($subjects as $subject) {
            $all = $all->concat($this->forSubject($subject));
        }

        return $all;
    }

    // 1つの授業の出欠を組み立てる
    /** @return Collection<int, array<string, mixed>> */
    private function build(Subject $subject): Collection
    {
        $year = $subject->academicYear;
        $to = today()->min($year->end_date); // 今日まで（年度が終わっていれば、年度の終わりまで）
        if ($to->lt($year->start_date)) {
            return collect(); // まだ始まっていない年度
        }

        // 1年分の休校・振替・授業変更をまとめて読む（コマごとに SQL を出さないため）
        $this->calendar->preload($year->start_date, $to);
        // 受講生と、そのクラス（在籍）。classIdsOn() もこれを使う
        $subject->loadMissing(['schedules', 'students.enrollments']);

        // 記録されている出欠を、コマ（日付・時限）ごとにまとめる。記録が1件でもあるコマ ＝ 行われたコマ
        $lessons = Attendance::query()
            ->where('subject_id', $subject->id)
            ->whereBetween('date', [$year->start_date->toDateString(), $to->toDateString()])
            ->get(['student_id', 'date', 'period', 'status'])
            ->groupBy(fn (Attendance $attendance) => $attendance->date.'|'.$attendance->period);

        $records = [];
        foreach ($lessons as $key => $rows) {
            [$day, $period] = explode('|', (string) $key);
            $date = Carbon::parse($day);
            $period = (int) $period;
            $statuses = $rows->pluck('status', 'student_id'); // 生徒ID => 記録された状態

            // 受けるはずの生徒 ＋ 記録がある生徒（受けるはずでなくても、記録があれば数える）
            // 受付がまだ終わっていないコマは、記録がある生徒だけ（まだ顔認証をしていない生徒を欠席にしない）
            $expected = CheckinWindow::for($date, $period)->hasClosed(now())
                ? $this->expectedStudentIds($subject, $date, $period)
                : collect();
            $studentIds = $expected
                ->concat($statuses->keys())
                ->map(fn ($id) => (int) $id)
                ->unique();

            foreach ($studentIds as $studentId) {
                $records[] = [
                    'student_id' => $studentId,
                    'subject_id' => $subject->id,
                    'date' => $date->toDateString(),
                    'month' => $date->format('Y-m'),
                    'weekday' => $date->dayOfWeekIso, // 1=月 … 7=日
                    'period' => $period,
                    'status' => $statuses->get($studentId, 'absent'), // 記録がなければ欠席
                ];
            }
        }

        return collect($records);
    }

    // そのコマを受けるはずの生徒（出席一覧の画面と同じ考え方）
    //   ・クラスの授業変更で、そのコマを受けないクラスの生徒は除く
    //   ・その日に予定の授業がない（手動で記録しただけの）コマは、受講生全員
    //   ・クラス未定の生徒は、どのコマでも受けるものとする
    /** @return Collection<int, int> */
    private function expectedStudentIds(Subject $subject, CarbonInterface $date, int $period): Collection
    {
        $classIds = $subject->periodsOn($date) !== [] ? $subject->classIdsOn($date, $period) : [];

        return $subject->students
            ->filter(function (Student $student) use ($subject, $classIds) {
                if ($classIds === []) {
                    return true;
                }
                $classId = $student->enrollments->firstWhere('academic_year_id', $subject->academic_year_id)?->school_class_id;

                return $classId === null || in_array($classId, $classIds, true);
            })
            ->pluck('id');
    }

    // ---------- 数える ----------

    // 出席・遅刻・公欠・欠席の数と、出席率（%。数えるコマがなければ null）
    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return array{present: int, late: int, excused: int, absent: int, total: int, rate: int|null}
     */
    public static function summarize(Collection $records): array
    {
        $counts = ['present' => 0, 'late' => 0, 'excused' => 0, 'absent' => 0];
        foreach ($records as $record) {
            $counts[$record['status']] = ($counts[$record['status']] ?? 0) + 1;
        }

        $total = array_sum($counts);
        $base = $total - $counts['excused']; // 公欠は分母から外す

        return $counts + [
            'total' => $total,
            'rate' => $base > 0 ? (int) round(($counts['present'] + $counts['late']) / $base * 100) : null,
        ];
    }

    // 出席率だけ（%。数えるコマがなければ null）
    /** @param  Collection<int, array<string, mixed>>  $records */
    public static function rate(Collection $records): ?int
    {
        return self::summarize($records)['rate'];
    }

    // 項目ごとの出席率（例：'month' なら ['2026-09' => 57, '2026-10' => 33]）
    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return array<int|string, int|null>
     */
    public static function ratesBy(Collection $records, string $field): array
    {
        return $records->groupBy($field)->map(fn (Collection $group) => self::rate($group))->all();
    }

    // 目立たせるか：出席率が LOW_RATE% 未満、または前の月より DROP ポイント以上下がった
    public static function isWarning(?int $rate, ?int $previous = null): bool
    {
        if ($rate === null) {
            return false;
        }

        return $rate < self::LOW_RATE || ($previous !== null && $previous - $rate >= self::DROP);
    }

    // 表の1行分：全体と、指定した月ごとの出席率（目立たせるかどうかも付ける）
    // 戻り値：['overall' => ['rate' => 64, 'warning' => true], 'months' => ['2026-11' => ['rate' => 100, 'warning' => false], ...]]
    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @param  array<string, string>  $months  月 => 画面の表示（monthsSoFar() の戻り値）
     * @return array{overall: array{rate: int|null, warning: bool}, months: array<string, array{rate: int|null, warning: bool}>}
     */
    public static function row(Collection $records, array $months): array
    {
        $byMonth = self::ratesBy($records, 'month');
        $overall = self::rate($records);

        $cells = [];
        foreach (array_keys($months) as $month) {
            $rate = $byMonth[$month] ?? null;
            $cells[$month] = [
                'rate' => $rate,
                'warning' => self::isWarning($rate, $byMonth[self::previousMonth($month)] ?? null),
            ];
        }

        return [
            'overall' => ['rate' => $overall, 'warning' => self::isWarning($overall)],
            'months' => $cells,
        ];
    }

    // ---------- 月 ----------

    // 年度の12か月（'2026-04' => '4月', …, '2027-03' => '3月'）
    /** @return array<string, string> */
    public static function yearMonths(AcademicYear $year): array
    {
        $months = [];
        for ($month = $year->start_date->copy()->startOfMonth(); $month->lte($year->end_date); $month->addMonth()) {
            $months[$month->format('Y-m')] = $month->month.'月';
        }

        return $months;
    }

    // 表に出す月：今月から年度の最初の月まで（新しい順。'2026-11' => '11月', …, '2026-04' => '4月'）
    // 年度が終わっていれば年度の最後の月から、まだ始まっていなければ最初の月だけ
    /** @return array<string, string> */
    public static function monthsSoFar(AcademicYear $year): array
    {
        $last = today()->min($year->end_date)->max($year->start_date)->format('Y-m');
        $months = array_filter(self::yearMonths($year), fn (string $month) => $month <= $last, ARRAY_FILTER_USE_KEY);

        return array_reverse($months, true); // 新しい月を左に出す（キーの '2026-11' などはそのまま）
    }

    // 前の月（'2026-01' → '2025-12'）
    public static function previousMonth(string $month): string
    {
        return Carbon::createFromFormat('!Y-m', $month)->subMonth()->format('Y-m');
    }

    // 折れ線グラフの点（月の順）。記録がない月は rate が null（点を打たない）
    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @param  array<string, string>  $months  yearMonths() の戻り値
     * @return list<array{label: string, rate: int|null}>
     */
    public static function monthlyPoints(Collection $records, array $months): array
    {
        $byMonth = self::ratesBy($records, 'month');

        return array_values(array_map(
            fn (string $month, string $label) => ['label' => $label, 'rate' => $byMonth[$month] ?? null],
            array_keys($months),
            $months,
        ));
    }
}