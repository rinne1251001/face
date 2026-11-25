<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\MatchLog;
use App\Models\Student;
use App\Models\Subject;
use App\Services\SchoolCalendar;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Database\Factories\AttendanceFactory;
use Database\Factories\MatchLogFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// テスト用の出席記録（今日を含む年度の、過去数週間分。今日の分は作らない）
// 実行：php artisan db:seed --class=TestAttendanceSeeder（TestDataSeeder の最後でも実行される）
// 何度実行してもよい（その期間の出席記録を消してから作り直す。実行した日から数えるので、日がたったら実行し直す）
//
// アプリの決まりに合わせて作る
//   ・授業がある日・時限は、アプリと同じ Subject::periodsOn()（祝日・休校・振替・授業変更も反映される）
//   ・出席：開始30分前〜開始時刻に顔認証 ／ 遅刻：開始時刻を過ぎてから顔認証 ／ どちらも一部は教師の手動
//   ・欠席：ほとんどは記録なし（顔認証をしなかった＝欠席）。一部は教師が手動で「欠席」
//   ・公欠：教師の手動だけ
class TestAttendanceSeeder extends Seeder
{
    private const WEEKS = 6; // 何週間分作るか

    // 生徒のタイプ => [割合（%）, 1日休む確率（%）, 遅刻する確率（%）]
    private const TYPES = [
        'good' => [75, 1, 2],      // ほとんど休まない
        'average' => [15, 4, 8],   // ときどき休む・遅れる
        'poor' => [5, 30, 12],     // よく休む（出席率が70%を下回り、分析で目立つ）
        'slipping' => [5, 1, 3],   // 最近だけ休みがち（後半の週は SLIPPING_LATER）→ 前の月より出席率が下がる
    ];

    private const SLIPPING_LATER = [35, 15]; // 「最近休みがち」の生徒の、後半の週の [1日休む確率, 遅刻する確率]

    private const ABSENT_BY_WEEKDAY = [1 => 1.5, 5 => 1.2]; // 月曜・金曜は休みが多め（書いていない曜日は1倍）

    private const JOB_HUNTING = 4;       // 最終学年の生徒が、就職活動で1日公欠になる確率（%）

    private const OTHER_EXCUSED = 0.5;   // それ以外の生徒が、1日公欠になる確率（%）

    private const SKIP_LESSON = 1;       // 教師が出欠を取らなかった授業の割合（%）。アプリでは「行われていない授業」になる

    private const PRESENT_BY_FACE = 95;  // 出席のうち、顔認証で記録した割合（残りは教師の手動）

    private const LATE_BY_FACE = 85;     // 遅刻のうち、顔認証で記録した割合

    private const ABSENT_MANUAL = 30;    // 欠席のうち、教師が手動で「欠席」と記録した割合（残りは記録なし）

    // 出席記録の列（まとめて INSERT するので、全ての行を同じ列・同じ順にそろえる）
    private const COLUMNS = ['student_id', 'subject_id', 'date', 'period', 'status', 'method', 'match_log_id', 'teacher_id', 'created_at', 'updated_at'];

    /** @var array<int, array{type: string, final: bool, class_id: int|null}> 生徒ID => 生徒のタイプなど */
    private array $profiles = [];

    /** @var array<string, int> 状態ごとの件数（最後に表示する） */
    private array $counts = ['present' => 0, 'late' => 0, 'excused' => 0, 'absent' => 0, 'missing' => 0, 'lessons' => 0];

    /** @var array<int, array{0: int, 1: int}> 生徒ID => [出席＋遅刻, 公欠を除いたコマ数]（最後に表示する） */
    private array $tally = [];

    public function run(): void
    {
        $year = AcademicYear::current();
        if ($year === null) {
            $this->command?->warn('今日を含む年度がないので、出席記録は作りませんでした。');

            return;
        }

        $to = today()->subDay()->min($year->end_date);                 // 昨日まで
        $from = today()->subWeeks(self::WEEKS)->max($year->start_date); // 年度の初めより前には作らない
        if ($from->gt($to)) {
            $this->command?->warn('年度が始まったばかりなので、出席記録は作りませんでした。');

            return;
        }
        $later = $to->copy()->subWeeks(intdiv(self::WEEKS, 2))->addDay(); // 後半の週の始まり

        fake()->seed(20261001); // 同じ日に実行すれば、毎回同じ記録になる

        $subjects = Subject::query()
            ->where('academic_year_id', $year->id)
            ->with(['schedules', 'teachers', 'students.enrollments'])
            ->orderBy('id')
            ->get();
        app(SchoolCalendar::class)->preload($from, $to); // 期間中の休校・振替・授業変更をまとめて読む
        $this->makeProfiles($year);

        DB::transaction(function () use ($subjects, $from, $to, $later) {
            $this->deleteOld($subjects, $from, $to);
            $this->insert($this->build($subjects, $from, $to, $later));
        });

        $this->report($from, $to);
    }

    // 生徒ごとのタイプ・最終学年か・その年度のクラス
    private function makeProfiles(AcademicYear $year): void
    {
        $students = Student::query()
            ->whereHas('enrollments', fn ($q) => $q->where('academic_year_id', $year->id))
            ->with(['department', 'enrollments' => fn ($q) => $q->where('academic_year_id', $year->id)])
            ->orderBy('id')
            ->get();

        foreach ($students as $student) {
            $enrollment = $student->enrollments->first();
            $years = $student->department->years;

            // タイプを割合で選ぶ（例：1〜75 なら good、76〜90 なら average …）
            $roll = fake()->numberBetween(1, 100);
            $type = 'good';
            foreach (self::TYPES as $name => [$percent]) {
                if ($roll <= $percent) {
                    $type = $name;
                    break;
                }
                $roll -= $percent;
            }

            $this->profiles[$student->id] = [
                'type' => $type,
                'final' => $years >= 2 && $enrollment !== null && $enrollment->grade >= $years, // 就職活動をする学年
                'class_id' => $enrollment?->school_class_id,
            ];
        }
    }

    // その期間の出席記録と、その照合ログを消す（作り直すため）
    /** @param Collection<int, Subject> $subjects */
    private function deleteOld(Collection $subjects, CarbonInterface $from, CarbonInterface $to): void
    {
        $old = Attendance::query()
            ->whereIn('subject_id', $subjects->modelKeys())
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        $logIds = (clone $old)->whereNotNull('match_log_id')->pluck('match_log_id');
        $old->delete();
        foreach ($logIds->chunk(1000) as $chunk) {
            MatchLog::whereIn('id', $chunk)->delete();
        }
    }

    // 出席記録を組み立てる（まだ保存しない）
    /**
     * @param  Collection<int, Subject>  $subjects
     * @return list<array{attendance: array<array-key, mixed>, log: array<array-key, mixed>|null}>
     */
    private function build(Collection $subjects, CarbonInterface $from, CarbonInterface $to, CarbonInterface $later): array
    {
        $rows = [];
        foreach (CarbonPeriod::create($from, $to) as $date) {
            $daily = []; // 生徒ID => その日1日の状態（absent：休み / excused：公欠 / null：ふつう）

            foreach ($subjects as $subject) {
                $periods = $subject->periodsOn($date); // 土日・祝日・休校なら空
                if ($periods === [] || $this->chance(self::SKIP_LESSON)) {
                    continue;
                }
                $teacherId = $subject->teachers->first()?->id; // 受付・手動の記録をする教師

                foreach ($periods as $period) {
                    $this->counts['lessons']++;
                    $isFirst = ! in_array($period - 1, $periods, true); // 続けて行う時限の1つ目か（遅刻は1つ目だけ）
                    $classIds = $subject->classIdsOn($date, $period);   // このコマを受けるクラス（授業変更を反映）

                    foreach ($subject->students as $student) {
                        $profile = $this->profiles[$student->id] ?? null;
                        if ($profile === null) {
                            continue;
                        }
                        // 授業変更で、このコマを受けないクラスの生徒は飛ばす（アプリの「受けるはずの生徒」と同じ）
                        if ($classIds !== [] && $profile['class_id'] !== null && ! in_array($profile['class_id'], $classIds, true)) {
                            continue;
                        }

                        if (! array_key_exists($student->id, $daily)) {
                            $daily[$student->id] = $this->dayStatus($profile, $date, $later);
                        }
                        $status = $daily[$student->id] ?? $this->lessonStatus($profile, $date, $period, $isFirst, $later);

                        $this->count($student->id, $status);
                        $row = $this->record($status, $student->id, $subject->id, $teacherId, $date, $period);
                        if ($row !== null) {
                            $rows[] = $row;
                        } else {
                            $this->counts['missing']++; // 記録なしの欠席
                        }
                    }
                }
            }
        }

        return $rows;
    }

    // その日1日の状態（1日休む人は、その日の授業がすべて欠席。公欠も同じ）
    /** @param array{type: string, final: bool, class_id: int|null} $profile */
    private function dayStatus(array $profile, CarbonInterface $date, CarbonInterface $later): ?string
    {
        if ($this->chance($profile['final'] ? self::JOB_HUNTING : self::OTHER_EXCUSED)) {
            return 'excused';
        }
        [$absent] = $this->rates($profile, $date, $later);

        return $this->chance($absent * (self::ABSENT_BY_WEEKDAY[$date->dayOfWeekIso] ?? 1)) ? 'absent' : null;
    }

    // 1つのコマの状態（その日に来ている生徒）
    /** @param array{type: string, final: bool, class_id: int|null} $profile */
    private function lessonStatus(array $profile, CarbonInterface $date, int $period, bool $isFirst, CarbonInterface $later): string
    {
        [$absent, $late] = $this->rates($profile, $date, $later);

        return match (true) {
            $this->chance($absent / 4) => 'absent', // その授業だけ休む
            $isFirst && $this->chance($period === 1 ? $late * 2 : $late) => 'late', // 1限は遅刻が多め
            default => 'present',
        };
    }

    // [1日休む確率, 遅刻する確率]（%）
    /**
     * @param  array{type: string, final: bool, class_id: int|null}  $profile
     * @return array{0: float, 1: float}
     */
    private function rates(array $profile, CarbonInterface $date, CarbonInterface $later): array
    {
        if ($profile['type'] === 'slipping' && $date->gte($later)) {
            return [(float) self::SLIPPING_LATER[0], (float) self::SLIPPING_LATER[1]];
        }

        return [(float) self::TYPES[$profile['type']][1], (float) self::TYPES[$profile['type']][2]];
    }

    // 1件の出席記録（と、顔認証なら照合ログ）。記録なしの欠席なら null
    /** @return array{attendance: array<array-key, mixed>, log: array<array-key, mixed>|null}|null */
    private function record(string $status, int $studentId, int $subjectId, ?int $teacherId, CarbonInterface $date, int $period): ?array
    {
        $start = Carbon::parse($date->toDateString().' '.config("school.periods.{$period}.start"));
        $end = Carbon::parse($date->toDateString().' '.config("school.periods.{$period}.end"));
        $length = (int) $start->diffInMinutes($end);
        $opensBefore = (int) config('school.checkin.opens_before', 30);

        // [顔認証か, 記録した時刻]
        [$byFace, $time] = match ($status) {
            'present' => $this->chance(self::PRESENT_BY_FACE)
                ? [true, $start->copy()->subMinutes(fake()->numberBetween(0, $opensBefore))]  // 開始30分前〜開始時刻
                : [false, $start->copy()->addMinutes(fake()->numberBetween(5, 30))],
            'late' => $this->chance(self::LATE_BY_FACE)
                ? [true, $start->copy()->addMinutes(fake()->numberBetween(1, $length - 1))]   // 開始時刻を過ぎてから
                : [false, $start->copy()->addMinutes(fake()->numberBetween(10, $length))],
            'absent' => $this->chance(self::ABSENT_MANUAL)
                ? [false, $end->copy()->addMinutes(fake()->numberBetween(0, 30))]             // 授業のあとで教師が記録
                : [false, null],                                                               // 記録なし
            default => [false, $date->copy()->setTime(8, fake()->numberBetween(0, 50))],       // 公欠：朝に教師が記録
        };
        if ($time === null) {
            return null;
        }

        $keys = ['student_id' => $studentId, 'subject_id' => $subjectId, 'date' => $date->toDateString(), 'period' => $period];
        $factory = AttendanceFactory::new()->status($status)->at($time);
        $factory = $byFace ? $factory->byFace() : $factory->manual($teacherId);

        return [
            'attendance' => array_merge(array_fill_keys(self::COLUMNS, null), $factory->raw($keys)),
            // 顔認証の記録には照合ログがある（照合した教師は、受付の画面を開いていた教師）
            'log' => $byFace ? MatchLogFactory::new()->at($time)->raw(['student_id' => $studentId, 'teacher_id' => $teacherId]) : null,
        ];
    }

    // まとめて保存する（照合ログを先に保存して、その ID を出席記録に入れる）
    /** @param list<array{attendance: array<array-key, mixed>, log: array<array-key, mixed>|null}> $rows */
    private function insert(array $rows): void
    {
        foreach (array_chunk($rows, 1000) as $chunk) {
            $logs = array_values(array_filter(array_column($chunk, 'log')));
            $ids = [];
            if ($logs !== []) {
                $before = (int) MatchLog::max('id');
                MatchLog::insert($logs);
                // 今入れた照合ログの ID（入れた順）
                $ids = MatchLog::where('id', '>', $before)->orderBy('id')->pluck('id')->all();
            }

            $next = 0;
            $attendances = [];
            foreach ($chunk as $row) {
                $attendance = $row['attendance'];
                if ($row['log'] !== null) {
                    $attendance['match_log_id'] = $ids[$next++];
                }
                $attendances[] = $attendance;
            }
            Attendance::insert($attendances);
        }
    }

    // 確率（%）で true
    private function chance(float $percent): bool
    {
        return fake()->randomFloat(3, 0, 100) < $percent;
    }

    // 生徒ごとの出席率の集計（最後に表示する）。欠席は記録なしも含む
    private function count(int $studentId, string $status): void
    {
        $this->counts[$status]++;
        $this->tally[$studentId] ??= [0, 0];
        if ($status !== 'excused') {
            $this->tally[$studentId][1]++;
            if ($status !== 'absent') {
                $this->tally[$studentId][0]++;
            }
        }
    }

    // 作った記録の数を表示する
    private function report(CarbonInterface $from, CarbonInterface $to): void
    {
        $low = collect($this->tally)->filter(fn ($t) => $t[1] > 0 && $t[0] / $t[1] * 100 < 70)->count();

        $this->command?->info(sprintf(
            '出席記録：%s〜%s（%dコマ）／ 出席 %d・遅刻 %d・公欠 %d・欠席 %d（ほかに記録なしの欠席 %d）／ 出席率が70%%未満の生徒 %d人',
            $from->format('n/j'),
            $to->format('n/j'),
            $this->counts['lessons'],
            $this->counts['present'],
            $this->counts['late'],
            $this->counts['excused'],
            $this->counts['absent'] - $this->counts['missing'],
            $this->counts['missing'],
            $low,
        ));
    }
}