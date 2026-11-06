<?php

namespace App\Models;

use App\Services\SchoolCalendar; 
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 授業。曜日と時限は subject_schedules に分けてあるので、週に何回あっても1つの授業として扱える
class Subject extends Model
{
    // 曜日の番号（Carbon の dayOfWeekIso と同じ：1=月 … 7=日）
    public const DAYS = [1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土', 7 => '日'];

    protected $fillable = ['name', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date', // Carbon（日付）として扱える
            'end_date' => 'date',
        ];
    }

    // 授業を行う曜日と時限（月曜→日曜の順）
    /** @return HasMany<SubjectSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(SubjectSchedule::class)->orderBy('day_of_week');
    }

    /** @return BelongsToMany<Student, $this> */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    /** @return BelongsToMany<Teacher, $this> */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // 1日の最大時限（config/school.php に書いた時限の数。フォームの選択肢やバリデーションに使う）
    public static function maxPeriod(): int
    {
        return count(config('school.periods'));
    }

    // 時間割の順（いちばん早い曜日・時限の順 → 授業名の順）に並べる
    // 使い方：Subject::orderBySchedule()->get()
    /** @param Builder<Subject> $query */
    public function scopeOrderBySchedule(Builder $query): void
    {
        $query->orderBy(
            SubjectSchedule::selectRaw('min(day_of_week * 100 + period_start)')
                ->whereColumn('subject_schedules.subject_id', 'subjects.id')
        )->orderBy('name');
    }

    // ある曜日に授業がある授業だけ
    // 使い方：Subject::onDay(today()->dayOfWeekIso)->get()
    /** @param Builder<Subject> $query */
    public function scopeOnDay(Builder $query, int $dayOfWeek): void
    {
        $query->whereHas('schedules', fn ($q) => $q->where('day_of_week', $dayOfWeek));
    }

        // 変則授業（日付を指定して行う授業）
    /** @return HasMany<SpecialClass, $this> */
    public function specialClasses(): HasMany
    {
        return $this->hasMany(SpecialClass::class)->orderBy('date');
    }

    // 授業の期間（開始日〜終了日）の中の日か。開始日・終了日が未設定なら制限なし
    public function isInTerm(CarbonInterface $date): bool
    {
        return ($this->start_date === null || $date->gte($this->start_date))
            && ($this->end_date === null || $date->lte($this->end_date));
    }

    // ある日の、毎週の時間割（授業がない日は null）
    // 祝日・休校の日は null、振替の日は振替先の曜日の時間割になる（SchoolCalendar が決める）
    public function scheduleOn(CarbonInterface $date): ?SubjectSchedule
    {
        if (! $this->isInTerm($date)) {
            return null;
        }

        $day = app(SchoolCalendar::class)->timetableDay($date);

        return $day === null ? null : $this->schedules->firstWhere('day_of_week', $day);
    }

    // ある日の変則授業（なければ null）
    public function specialClassOn(CarbonInterface $date): ?SpecialClass
    {
        return app(SchoolCalendar::class)->specialClassesOn($date)->get($this->id);
    }

    // ある日の時限の一覧（毎週の時間割 ＋ 変則授業。例：水曜なら [3, 4, 5]）。授業がない日は空
    /** @return list<int> */
    public function periodsOn(CarbonInterface $date): array
    {
        $periods = array_unique([
            ...($this->scheduleOn($date)?->periods() ?? []),
            ...($this->specialClassOn($date)?->periods() ?? []),
        ]);
        sort($periods);

        return $periods;
    }

    // 出席を取れる時限の一覧
    // その日が授業の曜日ならその日の時限。違う曜日（振替など）なら、この授業のどれかの時限を選べるようにする
    /** @return list<int> */
    public function periodsFor(CarbonInterface $date): array
    {
        $periods = $this->periodsOn($date);
        if ($periods === []) {
            $periods = range(1, self::maxPeriod()); // 変則授業だけの授業は、どの時限でも選べる
        }

        return $periods;
    }

    // 画面の ?period= の値を、その日の時限にそろえる（おかしな値や指定なしなら最初の時限）
    public function periodOrFirst(mixed $period, CarbonInterface $date): int
    {
        $periods = $this->periodsFor($date);

        return in_array((int) $period, $periods, true) ? (int) $period : ($periods[0] ?? 1);
    }

    // 画面表示用：「月曜 1限・水曜 3〜5限」（$subject->schedule_label で使える）
    /** @return Attribute<string, never> */
    protected function scheduleLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->schedules->pluck('label')->implode('・') ?: '不定期');
    }

    // 画面表示用：「月・水」（$subject->days_label で使える）
    /** @return Attribute<string, never> */
    protected function daysLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->schedules
            ->map(fn ($schedule) => self::DAYS[$schedule->day_of_week] ?? '?')
            ->implode('・'));
    }

    // 画面表示用：「4月1日〜7月31日」（$subject->term_label で使える。未設定なら null）
    /** @return Attribute<string|null, never> */
    protected function termLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->start_date && $this->end_date
            ? $this->start_date->format('n月j日').'〜'.$this->end_date->format('n月j日')
            : null);
    }
}