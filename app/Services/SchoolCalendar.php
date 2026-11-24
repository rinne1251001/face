<?php

namespace App\Services;

use App\Models\CalendarDay;
use App\Models\ClassLessonChange;
use App\Models\SpecialClass;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Yasumi\Yasumi;

// 学校のカレンダー：ある日に「どの日付の時間割で授業をするか」を決める
//   1. 日付ごとの設定（calendar_days）があれば、それに従う（休校 または 振替）
//   2. 祝日なら、授業なし（祝日は Yasumi というライブラリで自動的に判定する）
//   3. それ以外は、その日自身の時間割
// 変則授業（special_classes）は、これとは別に、その日付にだけ追加される
class SchoolCalendar
{
    /** @var array<int, \Yasumi\ProviderInterface> 年 => その年の祝日 */
    private array $holidays = [];

    /** @var array<string, CalendarDay|null> 日付 => 日付ごとの設定 */
    private array $days = [];

    /** @var array<string, Collection<int|string, SpecialClass>> 日付 => その日の変則授業（授業ID => SpecialClass） */
    private array $specialClasses = [];

    /** @var array<string, Collection<int, ClassLessonChange>> 日付 => その日のクラスごとの授業変更 */
    private array $lessonChanges = [];

    /** @var array<string, true> まとめて読み込み済みの期間（「開始日|終了日」） */
    private array $preloaded = [];

    // 祝日の名前（祝日でなければ null）。振替休日・国民の休日も含む
    public function holidayName(CarbonInterface $date): ?string
    {
        $holidays = $this->holidays[$date->year] ??= Yasumi::create('Japan', $date->year, 'ja_JP');

        foreach ($holidays->on($date) as $holiday) {
            return $holiday->getName();
        }

        return null;
    }

    // ある期間の祝日（日付 => 祝日の名前）。振替休日・国民の休日も含む
    /** @return array<string, string> */
    public function holidaysBetween(CarbonInterface $from, CarbonInterface $to): array
    {
        $first = $from->toDateString();
        $last = $to->toDateString();
        $list = [];
        for ($year = $from->year; $year <= $to->year; $year++) {
            $holidays = $this->holidays[$year] ??= Yasumi::create('Japan', $year, 'ja_JP');
            foreach ($holidays->getHolidays() as $holiday) {
                $key = $holiday->format('Y-m-d');
                if ($key >= $first && $key <= $last) {
                    $list[$key] = $holiday->getName();
                }
            }
        }
        ksort($list);

        return $list;
    }

    // 日付ごとの設定（なければ null）
    public function dayOverride(CarbonInterface $date): ?CalendarDay
    {
        $key = $date->toDateString();

        if (! array_key_exists($key, $this->days)) {
            $this->days[$key] = CalendarDay::whereDate('date', $key)->first();
        }

        return $this->days[$key];
    }

    // その日に使う時間割の日付。授業がない日（祝日・休校）は null
    //   ふつうの日はその日自身、振替の日は登録した日付（例：11/7（土）に 11/3（火）の授業 → 11/3）
    //   授業の曜日も、授業の期間（前期・後期など）も、この日付で判定する（Subject::scheduleOn）
    //   ※ 返した日付は読むだけにする（書き換えると、元の日付や登録の内容まで変わってしまう）
    public function timetableDate(CarbonInterface $date): ?CarbonInterface
    {
        $override = $this->dayOverride($date);
        if ($override !== null) {
            return $override->timetable_date; // 休校なら null
        }

        return $this->holidayName($date) === null ? $date : null;
    }

    // その日に使う時間割の曜日（1=月 … 7=日）。授業がない日（祝日・休校）は null
    public function timetableDay(CarbonInterface $date): ?int
    {
        return $this->timetableDate($date)?->dayOfWeekIso;
    }

    // 画面に出す、その日の説明（ふつうの日は null）
    // 例：「祝日（文化の日）」「休校（文化祭）」「水曜の時間割（11/3の振替）」
    public function dayNote(CarbonInterface $date): ?string
    {
        $override = $this->dayOverride($date);
        if ($override !== null) {
            return $override->label.($override->note ? "（{$override->note}）" : '');
        }

        $holiday = $this->holidayName($date);

        return $holiday === null ? null : "祝日（{$holiday}）";
    }

    // その日の変則授業（授業ID => SpecialClass）
    /** @return Collection<int|string, SpecialClass> */
    public function specialClassesOn(CarbonInterface $date): Collection
    {
        $key = $date->toDateString();

        return $this->specialClasses[$key] ??= SpecialClass::whereDate('date', $key)->get()->keyBy('subject_id');
    }

    // その日のクラスごとの授業変更
    /** @return Collection<int, ClassLessonChange> */
    public function lessonChangesOn(CarbonInterface $date): Collection
    {
        $key = $date->toDateString();

        return $this->lessonChanges[$key] ??= ClassLessonChange::whereDate('date', $key)->get();
    }

    // ある期間の「日付ごとの設定・変則授業・授業変更」を、まとめて読み込んでおく
    // （分析で1年分の日付を調べるときに、日付ごとに SQL を出さないようにするため。読み込むのは3回だけ）
    public function preload(CarbonInterface $from, CarbonInterface $to): void
    {
        $first = $from->toDateString();
        $last = $to->toDateString();
        if ($first > $last || isset($this->preloaded["{$first}|{$last}"])) {
            return;
        }

        // whereDate で比べる（whereBetween だと、日付を「2026-12-06 00:00:00」の形で保存する DB（SQLite）で最終日が漏れる）
        $between = fn ($query) => $query->whereDate('date', '>=', $first)->whereDate('date', '<=', $last);
        $days = $between(CalendarDay::query())->get()->keyBy(fn (CalendarDay $day) => $day->date->toDateString());
        $specials = $between(SpecialClass::query())->get()->groupBy(fn (SpecialClass $special) => $special->date->toDateString());
        $changes = $between(ClassLessonChange::query())->get()->groupBy(fn (ClassLessonChange $change) => $change->date->toDateString());

        // その期間のすべての日付を埋める（何もない日も「何もない」と覚えておけば、あとで SQL を出さずに済む）
        foreach (CarbonPeriod::create($first, $last) as $date) {
            $key = $date->toDateString();
            if (! array_key_exists($key, $this->days)) {
                $this->days[$key] = $days->get($key);
            }
            $this->specialClasses[$key] ??= ($specials->get($key) ?? new EloquentCollection)->keyBy('subject_id');
            $this->lessonChanges[$key] ??= $changes->get($key) ?? new EloquentCollection;
        }

        $this->preloaded["{$first}|{$last}"] = true;
    }

    // 覚えている内容を捨てる（CalendarDay・SpecialClass・ClassLessonChange を保存・削除したときに呼ばれる）
    public function flush(): void
    {
        $this->days = [];
        $this->specialClasses = [];
        $this->lessonChanges = [];
        $this->preloaded = [];
    }
}