<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

// 授業を行う曜日と時限（例：月曜 1限、水曜 3〜5限）
class SubjectSchedule extends Model
{
    protected $fillable = ['day_of_week', 'period_start', 'period_end'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'period_start' => 'integer',
            'period_end' => 'integer',
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // この曜日の時限の一覧（例：3〜5限なら [3, 4, 5]）
    /** @return list<int> */
    public function periods(): array
    {
        return range($this->period_start, $this->period_end);
    }

    // 画面表示用：「月曜 1限」「水曜 3〜5限」
    /** @return Attribute<string, never> */
    protected function label(): Attribute
    {
        return Attribute::make(get: function () {
            $day = Subject::DAYS[$this->day_of_week] ?? '?';
            $period = $this->period_start === $this->period_end
                ? "{$this->period_start}限"
                : "{$this->period_start}〜{$this->period_end}限";

            return "{$day}曜 {$period}";
        });
    }

    // 開始・終了時刻（例：'09:00'）。続く時限は、最初の時限の開始〜最後の時限の終了
    /** @return Attribute<string|null, never> */
    protected function startTime(): Attribute
    {
        return Attribute::make(get: fn () => config("school.periods.{$this->period_start}.start"));
    }

    /** @return Attribute<string|null, never> */
    protected function endTime(): Attribute
    {
        return Attribute::make(get: fn () => config("school.periods.{$this->period_end}.end"));
    }

    // ある日の、開始・終了の日時（遅刻の判定や「いま行われている授業」を探すのに使う）
    public function startsAt(CarbonInterface $date): Carbon
    {
        return Carbon::parse($date->toDateString().' '.$this->timeOrFail($this->start_time, $this->period_start));
    }

    public function endsAt(CarbonInterface $date): Carbon
    {
        return Carbon::parse($date->toDateString().' '.$this->timeOrFail($this->end_time, $this->period_end));
    }

    // config に時刻が書かれていない時限なら、すぐにエラーにする
    // （そのままだと「0時0分」として扱われ、遅刻の判定などが黙って間違うため）
    private function timeOrFail(?string $time, int $period): string
    {
        return $time ?? throw new \LogicException("{$period}限の時刻が config/school.php にありません。");
    }
}