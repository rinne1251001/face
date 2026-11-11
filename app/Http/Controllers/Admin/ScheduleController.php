<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarDay;
use App\Models\ClassLessonChange;
use App\Models\SchoolClass;
use App\Models\SpecialClass;
use App\Models\Subject;
use App\Services\SchoolCalendar;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// 時間割の確認と、休校・振替・変則授業の登録（管理者のみ）
// 祝日は自動で休みになるので、登録するのは学校独自の休み・振替・変則授業だけ
class ScheduleController extends Controller
{
    // 1週間の時間割（?date=2026-11-09 でその日を含む週。省略すると今週）
    public function index(Request $request, SchoolCalendar $calendar): View
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $monday = Carbon::parse($request->query('date', today()->toDateString()))->startOfWeek(Carbon::MONDAY);

        $subjects = Subject::with(['schedules', 'students'])->orderBy('name')->get();

        // 月〜日の7日分：日付・曜日・その日の説明（祝日・休校・振替）
        // （土日は、授業の表を作ったあとで「授業がある日」だけに絞り込む）
        $days = collect(range(0, 6))->map(function ($i) use ($monday, $calendar) {
            $date = $monday->copy()->addDays($i);

            return [
                'date' => $date,
                'label' => Subject::DAYS[$date->dayOfWeekIso],
                'note' => $calendar->dayNote($date),            // 例：祝日（文化の日）、水曜の時間割（振替）
                'off' => $calendar->timetableDay($date) === null, // 毎週の授業がない日
            ];
        });

        // $grid[時限]['2026-11-09'] = その枠の授業の一覧（name … 授業名、special … 変則授業なら true）
        $grid = [];
        foreach ($days as $day) {
            $key = $day['date']->toDateString();
            foreach ($subjects as $subject) {
                $weekly = $subject->scheduleOn($day['date'])?->periods() ?? [];       // 毎週の時間割
                $special = $subject->specialClassOn($day['date'])?->periods() ?? []; // その日だけの変則授業

                // 授業変更を反映した時限（全てのクラスがやめた時限は、ここに出てこない）
                foreach ($subject->periodsOn($day['date']) as $period) {
                    $grid[$period][$key][] = [
                        'name' => $subject->name,
                        'special' => ! in_array($period, $weekly, true) && in_array($period, $special, true),
                    ];
                }
            }
        }

        // 土日は、その日に授業が1つでもあるときだけ表示する（月〜金はいつも表示）
        $hasLessons = fn ($date) => collect($grid)->contains(fn ($byDate) => isset($byDate[$date->toDateString()]));
        $days = $days->filter(fn ($day) => $day['date']->isWeekday() || $hasLessons($day['date']))->values();

        // 表示する最後の時限：6限（config の always_shown_periods）まではいつも表示し、
        // それより後ろの時限（7限）は、その週に授業があるときだけ表示する
        $lastPeriod = max([config()->integer('school.always_shown_periods'), ...array_keys($grid)]);

        // 今日以降にかかる授業変更（移動・入れ替えの2行は1つにまとめる）
        $groupIds = ClassLessonChange::whereDate('date', '>=', today())->distinct()->pluck('group_id');
        $lessonChanges = ClassLessonChange::with(['schoolClass', 'fromSubject', 'toSubject'])
            ->whereIn('group_id', $groupIds)
            ->orderBy('id')
            ->get()
            ->groupBy('group_id')
            ->map(fn ($rows) => [
                'group_id' => (string) $rows->first()?->group_id,
                'date' => $rows->min('date'),
                'text' => ClassLessonChange::describeGroup($rows->values()),
                'note' => $rows->first()?->note,
            ])
            ->sortBy('date')
            ->values();

        return view('admin.schedules.index', [
            'monday' => $monday,
            'isThisWeek' => $monday->isSameDay(today()->startOfWeek(Carbon::MONDAY)),
            'days' => $days,
            'grid' => $grid,
            'lastPeriod' => $lastPeriod,
            'maxPeriod' => Subject::maxPeriod(),
            // 今日以降に登録してある休校・振替と変則授業
            'calendarDays' => CalendarDay::whereDate('date', '>=', today())->orderBy('date')->get(),
            'lessonChanges' => $lessonChanges,
            'specialClasses' => SpecialClass::with('subject')
                ->whereDate('date', '>=', today())
                ->orderBy('date')
                ->orderBy('period_start')
                ->get(),
        ]);
    }

    // 登録の画面（?date= で日付を入れておける）
    public function create(Request $request): View
    {
        return view('admin.schedules.form', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'date' => $request->query('date'),
        ]);
    }

    // 休校・振替を登録する（同じ日付がすでにあれば上書き）
    public function storeDay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'day_of_week' => ['nullable', 'integer', Rule::in(array_keys(Subject::DAYS))], // 空なら休校
            'note' => ['nullable', 'string', 'max:100'],
        ], [], ['date' => '日付', 'day_of_week' => '時間割', 'note' => 'メモ']);

        // whereDate で探す（DB によって日付の保存のされ方が違っても、日付だけで比べられるように）
        $day = CalendarDay::whereDate('date', $data['date'])->first() ?? new CalendarDay(['date' => $data['date']]);
        $day->fill([
            'day_of_week' => $data['day_of_week'] ?? null,
            'note' => $data['note'] ?? null,
        ])->save();

        return redirect()->route('admin.schedules.index', ['date' => $data['date']])
            ->with('status', "{$day->date->format('n月j日')}を「{$day->label}」にしました。");
    }

    public function destroyDay(CalendarDay $calendarDay): RedirectResponse
    {
        $calendarDay->delete();

        return back()->with('status', "{$calendarDay->date->format('n月j日')}の設定を取り消しました。");
    }

    // 変則授業を登録する（同じ授業・同じ日付がすでにあれば上書き）
    public function storeSpecialClass(Request $request): RedirectResponse
    {
        $max = Subject::maxPeriod();
        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'period_start' => ['required', 'integer', "between:1,$max"],
            // 終了時限は開始時限以上（1限だけなら開始と同じ）
            'period_end' => ['required', 'integer', "between:1,$max", 'gte:period_start'],
            'note' => ['nullable', 'string', 'max:100'],
        ], [], [
            'subject_id' => '授業',
            'date' => '日付',
            'period_start' => '開始時限',
            'period_end' => '終了時限',
            'note' => 'メモ',
        ]);

        $special = SpecialClass::where('subject_id', $data['subject_id'])->whereDate('date', $data['date'])->first()
            ?? new SpecialClass(['subject_id' => $data['subject_id'], 'date' => $data['date']]);
        $special->fill($data)->save();

        return redirect()->route('admin.schedules.index', ['date' => $data['date']])
            ->with('status', "「{$special->subject->name}」の変則授業を{$special->date->format('n月j日')}に登録しました。");
    }

    public function destroySpecialClass(SpecialClass $specialClass): RedirectResponse
    {
        $specialClass->delete();

        return back()->with('status', '変則授業を取り消しました。');
    }

    // あるクラスの、ある日の授業の一覧（授業変更の画面で「どの授業か」を選ぶために、画面の JavaScript から呼ぶ）
    public function lessons(Request $request): JsonResponse
    {
        $data = $request->validate([
            'school_class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json($this->lessonsFor((int) $data['school_class_id'], Carbon::parse($data['date'])));
    }

    // クラスの授業変更を登録する
    public function storeLessonChange(Request $request): RedirectResponse
    {
        $max = Subject::maxPeriod();
        $lessonFormat = 'regex:/^\d+:\d+:\d+$/'; // 「授業ID:開始時限:終了時限」

        $data = $request->validate([
            'school_class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'type' => ['required', Rule::in(array_keys(ClassLessonChange::TYPES))],
            'date' => ['required', 'date_format:Y-m-d'],
            'lesson' => ['exclude_if:type,add', 'required', $lessonFormat],
            // 元の授業のうち、変更する時限（2時限以上続く授業で一部だけ変えるとき。省略すると全部）
            'lesson_period_start' => ['exclude_if:type,add', 'nullable', 'integer', "between:1,$max"],
            'lesson_period_end' => ['exclude_if:type,add', 'nullable', 'integer', "between:1,$max", 'gte:lesson_period_start'],
            'to_subject_id' => ['exclude_unless:type,change,add', 'required', 'integer', 'exists:subjects,id'],
            'to_date' => ['exclude_unless:type,move,swap', 'required', 'date_format:Y-m-d'],
            'to_period_start' => ['exclude_unless:type,move,add', 'required', 'integer', "between:1,$max"],
            'to_period_end' => ['exclude_unless:type,move,add', 'required', 'integer', "between:1,$max", 'gte:to_period_start'],
            'to_lesson' => ['exclude_unless:type,swap', 'required', $lessonFormat],
            // 相手の授業のうち、入れ替える時限（2時限以上続く授業で一部だけ入れ替えるとき。省略すると全部）
            'to_lesson_period_start' => ['exclude_unless:type,swap', 'nullable', 'integer', "between:1,$max"],
            'to_lesson_period_end' => ['exclude_unless:type,swap', 'nullable', 'integer', "between:1,$max", 'gte:to_lesson_period_start'],
            'note' => ['nullable', 'string', 'max:100'],
        ], [], [
            'school_class_id' => 'クラス',
            'type' => '変更の種類',
            'date' => '日付',
            'lesson' => '授業',
            'lesson_period_start' => '変更する時限（開始）',
            'lesson_period_end' => '変更する時限（終了）',
            'to_subject_id' => '代わりにする授業',
            'to_date' => '移動先・入れ替える相手の日付',
            'to_period_start' => '開始時限',
            'to_period_end' => '終了時限',
            'to_lesson' => '入れ替える相手の授業',
            'to_lesson_period_start' => '入れ替える時限（開始）',
            'to_lesson_period_end' => '入れ替える時限（終了）',
            'note' => 'メモ',
        ]);

        $classId = (int) $data['school_class_id'];
        $type = (string) $data['type'];
        $date = Carbon::parse((string) $data['date']);
        $base = [
            'group_id' => (string) Str::uuid(),
            'type' => $type,
            'school_class_id' => $classId,
            'note' => $data['note'] ?? null,
        ];

        // 元の授業（このクラスが、その日・その時限に、本当にその授業を受けるか確かめる）
        // 時限が選ばれていれば、その時限だけを変更する（例：3〜5限の授業の4限だけ）
        [$subjectId, $start, $end] = $type === 'add'
            ? [null, (int) $data['to_period_start'], (int) $data['to_period_end']]
            : $this->lessonOrFail(
                $classId, $date, (string) $data['lesson'],
                self::intOrNull($data, 'lesson_period_start'), self::intOrNull($data, 'lesson_period_end'), 'lesson',
            );
        $slot = ['date' => $date->toDateString(), 'period_start' => $start, 'period_end' => $end];

        // 移動先の時限の数は、元の授業とそろえる（1時限の授業が3時限分に増えたりしないように）
        if ($type === 'move' && (int) $data['to_period_end'] - (int) $data['to_period_start'] !== $end - $start) {
            $count = $end - $start + 1;
            throw ValidationException::withMessages([
                'to_period_end' => "移動先の時限の数を、元の授業（{$count}時限）とそろえてください。",
            ]);
        }

        $rows = match ($type) {
            'change' => [$base + $slot + ['from_subject_id' => $subjectId, 'to_subject_id' => (int) $data['to_subject_id']]],
            'cancel' => [$base + $slot + ['from_subject_id' => $subjectId, 'to_subject_id' => null]],
            'add' => [$base + $slot + ['from_subject_id' => null, 'to_subject_id' => (int) $data['to_subject_id']]],
            'move' => [
                $base + $slot + ['from_subject_id' => $subjectId, 'to_subject_id' => null],
                $base + [
                    'date' => (string) $data['to_date'],
                    'period_start' => (int) $data['to_period_start'],
                    'period_end' => (int) $data['to_period_end'],
                    'from_subject_id' => null,
                    'to_subject_id' => $subjectId,
                ],
            ],
            'swap' => $this->swapRows(
                $base, $slot, (int) $subjectId, $start, $end, $classId, (string) $data['to_date'], (string) $data['to_lesson'],
                self::intOrNull($data, 'to_lesson_period_start'), self::intOrNull($data, 'to_lesson_period_end'),
            ),
            default => [],
        };

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                ClassLessonChange::create($row);
            }
        });

        $created = ClassLessonChange::with(['schoolClass', 'fromSubject', 'toSubject'])->where('group_id', $base['group_id'])->orderBy('id')->get();

        return redirect()->route('admin.schedules.index', ['date' => $date->toDateString()])
            ->with('status', '授業変更を登録しました：'.ClassLessonChange::describeGroup($created));
    }

    // 授業変更を取り消す
    public function destroyLessonChange(string $group): RedirectResponse
    {
        ClassLessonChange::where('group_id', $group)->get()->each->delete();

        return back()->with('status', '授業変更を取り消しました。');
    }

    // 入れ替えの2行を作る（相手の授業も、このクラスがその日・その時限に受けるものか確かめる）
    // 入れ替える時限の数はそろえる（1時限の授業と3時限の授業なら、相手の3時限のうち1時限を選んでもらう）
    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $slot
     * @return list<array<string, mixed>>
     */
    private function swapRows(
        array $base, array $slot, int $subjectId, int $start, int $end,
        int $classId, string $toDate, string $toLesson, ?int $toFrom, ?int $toTo,
    ): array {
        [$otherId, $otherStart, $otherEnd] = $this->lessonOrFail($classId, Carbon::parse($toDate), $toLesson, $toFrom, $toTo, 'to_lesson');

        $count = $end - $start + 1;
        $otherCount = $otherEnd - $otherStart + 1;
        if ($count !== $otherCount) {
            throw ValidationException::withMessages([
                'to_lesson' => "入れ替える時限の数をそろえてください（元の授業：{$count}時限、相手：{$otherCount}時限）。"
                    .'2時限以上続く授業は「時限」で、入れ替える時限を選べます。',
            ]);
        }

        // 同じ授業の、重なっている時限どうしは入れ替えられない
        if ($otherId === $subjectId && $toDate === $slot['date'] && $otherStart <= $end && $start <= $otherEnd) {
            throw ValidationException::withMessages(['to_lesson' => '同じ授業どうしは入れ替えられません。']);
        }

        return [
            $base + $slot + ['from_subject_id' => $subjectId, 'to_subject_id' => $otherId],
            $base + ['date' => $toDate, 'period_start' => $otherStart, 'period_end' => $otherEnd, 'from_subject_id' => $otherId, 'to_subject_id' => $subjectId],
        ];
    }

    // 「授業ID:開始時限:終了時限」を読み取り、そのクラスがその日にその授業を受けるか確かめる
    // $from・$to … その授業のうち、使う時限（2時限以上続く授業で一部だけ使うとき。null なら全部）
    /** @return array{int, int, int} [授業ID, 開始時限, 終了時限] */
    private function lessonOrFail(int $classId, CarbonInterface $date, string $lesson, ?int $from, ?int $to, string $field): array
    {
        [$subjectId, $start, $end] = array_map('intval', explode(':', $lesson));

        // 時限が選ばれていれば、その授業の時限の中かどうかを確かめて、その時限だけにする
        if ($from !== null && $to !== null) {
            if ($from < $start || $to > $end || $from > $to) {
                $range = $start === $end ? "{$start}限" : "{$start}〜{$end}限";
                throw ValidationException::withMessages([$field => "時限は、選んだ授業の時限（{$range}）の中から選んでください。"]);
            }
            [$start, $end] = [$from, $to];
        }

        $subject = Subject::with(['schedules', 'students'])->find($subjectId);
        foreach (range($start, max($start, $end)) as $period) {
            if ($subject === null || ! in_array($classId, $subject->classIdsOn($date, $period), true)) {
                throw ValidationException::withMessages([
                    $field => 'このクラスは、その日・その時限にこの授業がありません。画面の一覧から選び直してください。',
                ]);
            }
        }

        return [$subjectId, $start, $end];
    }

    // 入力の値を int で取り出す（空なら null）
    /** @param  array<string, mixed>  $data */
    private static function intOrNull(array $data, string $key): ?int
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (int) $data[$key] : null;
    }

    private function lessonsFor(int $classId, CarbonInterface $date): array
    {
        $changedIds = ClassLessonChange::whereDate('date', $date->toDateString())
            ->where('school_class_id', $classId)
            ->whereNotNull('to_subject_id')
            ->pluck('to_subject_id');
        $subjects = Subject::with(['schedules', 'students'])
            ->where(fn ($q) => $q
                ->whereHas('students', fn ($s) => $s->where('school_class_id', $classId))
                ->orWhereIn('id', $changedIds))
            ->get();

        $lessons = [];
        foreach ($subjects as $subject) {
            $periods = array_values(array_filter(
                $subject->periodsOn($date),
                fn (int $period) => in_array($classId, $subject->classIdsOn($date, $period), true),
            ));
            foreach (self::ranges($periods) as [$start, $end]) {
                $lessons[] = [
                    'value' => "{$subject->id}:{$start}:{$end}",
                    'label' => ($start === $end ? "{$start}限" : "{$start}〜{$end}限").' '.$subject->name,
                    'subject_id' => $subject->id,
                    'period_start' => $start,
                    'period_end' => $end,
                ];
            }
        }
        usort($lessons, fn (array $a, array $b) => $a['period_start'] <=> $b['period_start']);

        return $lessons;
    }

    private static function ranges(array $periods): array
    {
        $ranges = [];
        foreach ($periods as $period) {
            $last = array_key_last($ranges);
            if ($last !== null && $ranges[$last][1] === $period - 1) {
                $ranges[$last][1] = $period;
            } else {
                $ranges[] = [$period, $period];
            }
        }

        return $ranges;
    }
}