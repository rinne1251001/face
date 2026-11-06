<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarDay;
use App\Models\SpecialClass;
use App\Models\Subject;
use App\Services\SchoolCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
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

        $subjects = Subject::with('schedules')->orderBy('name')->get();

        // 月〜金の5日分：日付・曜日・その日の説明（祝日・休校・振替）
        $days = collect(range(0, 4))->map(function ($i) use ($monday, $calendar) {
            $date = $monday->copy()->addDays($i);

            return [
                'date' => $date,
                'label' => Subject::DAYS[$date->dayOfWeekIso],
                'note' => $calendar->dayNote($date),             // 例：祝日（文化の日）、水曜の時間割（振替）
                'off' => $calendar->timetableDay($date) === null, // 毎週の授業がない日
            ];
        });

        // $grid[時限]['2026-11-09'] = その枠の授業の一覧（['name' => 授業名, 'special' => 変則授業なら true]）
        $grid = [];
        foreach ($days as $day) {
            $key = $day['date']->toDateString();
            foreach ($subjects as $subject) {
                $weekly = $subject->scheduleOn($day['date'])?->periods() ?? [];       // 毎週の時間割
                $special = $subject->specialClassOn($day['date'])?->periods() ?? []; // その日だけの変則授業
                foreach (array_unique([...$weekly, ...$special]) as $period) {
                    $grid[$period][$key][] = [
                        'name' => $subject->name,
                        'special' => ! in_array($period, $weekly, true),
                    ];
                }
            }
        }

        return view('admin.schedules.index', [
            'monday' => $monday,
            'isThisWeek' => $monday->isSameDay(today()->startOfWeek(Carbon::MONDAY)),
            'days' => $days,
            'grid' => $grid,
            'maxPeriod' => Subject::maxPeriod(),
            // 今日以降に登録してある休校・振替と変則授業
            'calendarDays' => CalendarDay::whereDate('date', '>=', today())->orderBy('date')->get(),
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
}