<?php

namespace App\Models;

use App\Services\SchoolCalendar;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

// 日付ごとの例外（休校・振替）
//   timetable_date が null なら授業なし、日付ならその日付の時間割で授業（例：11/7 に 11/3（火）の授業）
class CalendarDay extends Model
{
    protected $fillable = ['date', 'timetable_date', 'note'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'timetable_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // 変更したら、SchoolCalendar が覚えている内容を捨てる（古い内容で判定しないように）
        static::saved(fn () => app(SchoolCalendar::class)->flush());
        static::deleted(fn () => app(SchoolCalendar::class)->flush());
    }

    // 画面表示用：「休校」「11/3（火）の時間割」
    // その日自身の時間割（祝日に授業をする日）は「火曜の時間割」
    /** @return Attribute<string, never> */
    protected function label(): Attribute
    {
        return Attribute::make(get: function () {
            $source = $this->timetable_date;
            if ($source === null) {
                return '休校';
            }
            $day = Subject::DAYS[$source->dayOfWeekIso];

            return $source->isSameDay($this->date) ? "{$day}曜の時間割" : $source->format('n/j')."（{$day}）の時間割";
        });
    }
}