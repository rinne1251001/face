<?php

namespace App\Models;

use App\Services\SchoolCalendar;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

// 日付ごとの例外（休校・振替）。day_of_week が null なら授業なし、1〜7 ならその曜日の時間割で授業
class CalendarDay extends Model
{
    protected $fillable = ['date', 'day_of_week', 'note'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'day_of_week' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // 変更したら、SchoolCalendar が覚えている内容を捨てる（古い内容で判定しないように）
        static::saved(fn () => app(SchoolCalendar::class)->flush());
        static::deleted(fn () => app(SchoolCalendar::class)->flush());
    }

    // 画面表示用：「休校」「水曜の時間割」
    /** @return Attribute<string, never> */
    protected function label(): Attribute
    {
        return Attribute::make(get: fn () => $this->day_of_week === null
            ? '休校'
            : Subject::DAYS[$this->day_of_week].'曜の時間割');
    }
}