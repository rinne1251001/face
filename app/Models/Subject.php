<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Subject extends Model
{
    // 曜日の番号（Carbon の dayOfWeekIso と同じ：1=月 … 7=日）
    public const DAYS = [1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土', 7 => '日'];

    protected $fillable = ['name', 'day_of_week', 'period_start', 'period_end', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'period_start' => 'integer',
            'period_end' => 'integer',
            'start_date' => 'date', // Carbon（日付）として扱える
            'end_date' => 'date',
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

    // 1日の最大時限（config/school.php に書いた時限の数。フォームの選択肢やバリデーションに使う）
    public static function maxPeriod(): int
    {
        return count(config('school.periods'));
    }

    // 授業の開始時刻（例：'09:15'）。3〜5限のように続く授業は、最初の時限の開始時刻
    protected function startTime(): Attribute
    {
        return Attribute::get(fn () => config("school.periods.{$this->period_start}.start"));
    }

    // 授業の終了時刻（例：'18:30'）。続く授業は、最後の時限の終了時刻
    protected function endTime(): Attribute
    {
        return Attribute::get(fn () => config("school.periods.{$this->period_end}.end"));
    }

    // ある日の、授業の開始・終了の日時（遅刻の判定や「いま行われている授業」を探すのに使う）
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

    // この授業の時限の一覧（例：3〜5限の授業なら [3, 4, 5]）
    public function periods(): array
    {
        return range($this->period_start, $this->period_end);
    }

    // 画面の ?period= の値を、この授業の時限にそろえる（おかしな値や指定なしなら最初の時限）
    public function periodOrFirst(mixed $period): int
    {
        return in_array((int) $period, $this->periods(), true) ? (int) $period : $this->period_start;
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
    
    // 画面表示用：「4月1日〜7月31日」（$subject->term_label で使える。未設定なら null）
    protected function termLabel(): Attribute
    {
        return Attribute::get(fn () => $this->start_date && $this->end_date
            ? $this->start_date->format('n月j日').'〜'.$this->end_date->format('n月j日')
            : null);
    }
}
