<?php

namespace App\Services;

use App\Models\CalendarDay;
use App\Models\SpecialClass;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Yasumi\Yasumi;

// 学校のカレンダー：ある日に「どの曜日の時間割で授業をするか」を決める
//   1. 日付ごとの設定（calendar_days）があれば、それに従う（休校 または 振替）
//   2. 祝日なら、授業なし（祝日は Yasumi というライブラリで自動的に判定する）
//   3. それ以外は、その日の曜日の時間割
// 変則授業（special_classes）は、これとは別に、その日付にだけ追加される
class SchoolCalendar
{
    /** @var array<int, \Yasumi\ProviderInterface> 年 => その年の祝日 */
    private array $holidays = [];

    /** @var array<string, CalendarDay|null> 日付 => 日付ごとの設定 */
    private array $days = [];

    /** @var array<string, Collection> 日付 => その日の変則授業（授業ID => SpecialClass） */
    private array $specialClasses = [];

    // 祝日の名前（祝日でなければ null）。振替休日・国民の休日も含む
    public function holidayName(CarbonInterface $date): ?string
    {
        $holidays = $this->holidays[$date->year] ??= Yasumi::create('Japan', $date->year, 'ja_JP');

        foreach ($holidays->on($date) as $holiday) {
            return $holiday->getName();
        }

        return null;
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

    // その日に使う時間割の曜日（1=月 … 7=日）。授業がない日（祝日・休校）は null
    public function timetableDay(CarbonInterface $date): ?int
    {
        $override = $this->dayOverride($date);
        if ($override !== null) {
            return $override->day_of_week; // 休校なら null、振替ならその曜日
        }

        return $this->holidayName($date) === null ? $date->dayOfWeekIso : null;
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
    public function specialClassesOn(CarbonInterface $date): Collection
    {
        $key = $date->toDateString();

        return $this->specialClasses[$key] ??= SpecialClass::whereDate('date', $key)->get()->keyBy('subject_id');
    }

    // 覚えている内容を捨てる（CalendarDay・SpecialClass を保存・削除したときに呼ばれる）
    public function flush(): void
    {
        $this->days = [];
        $this->specialClasses = [];
    }
}