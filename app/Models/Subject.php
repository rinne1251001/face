<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    // 曜日の番号（Carbon の dayOfWeekIso と同じ：1=月 … 7=日）
    public const DAYS = [1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土', 7 => '日'];

    // 1日の最大時限（フォームの選択肢に使う）
    public const MAX_PERIOD = 10;

    protected $fillable = ['name', 'day_of_week', 'period_start', 'period_end'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'period_start' => 'integer',
            'period_end' => 'integer',
        ];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // 画面表示用：「月曜 1限」「水曜 3〜5限」（$subject->schedule_label で使える）
    protected function scheduleLabel(): Attribute
    {
        return Attribute::get(function () {
            $day = self::DAYS[$this->day_of_week] ?? '?';
            $period = $this->period_start === $this->period_end
                ? "{$this->period_start}限"
                : "{$this->period_start}〜{$this->period_end}限";

            return "{$day}曜 {$period}";
        });
    }
}
