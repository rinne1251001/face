<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 年度（例：2027年度 ＝ 2027年4月1日〜2028年3月31日）
// クラス・授業・在籍は、必ずどれか1つの年度に属する
class AcademicYear extends Model
{
    protected $fillable = ['year', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'unlocked_at' => 'datetime', // 編集を許可した日時（読み取り専用なら null）
        ];
    }

    /** @return HasMany<SchoolClass, $this> */
    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /** @return HasMany<Subject, $this> */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    // 在籍（この年度にいる生徒の分）
    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    // その日を含む年度（なければ null）
    public static function containing(CarbonInterface $date): ?self
    {
        return static::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    // 今年度（今日を含む年度）。日付で決まるので、4月1日になると自動で切り替わる
    public static function current(): ?self
    {
        return static::containing(today());
    }

    // 今年度。今年度がなければ（年度と年度のあいだの日など）、いちばん新しい年度
    public static function currentOrNewest(): ?self
    {
        return static::current() ?? static::newest();
    }

    // いちばん新しい年度（年度更新のコピー元になる）
    public static function newest(): ?self
    {
        return static::query()->orderByDesc('year')->first();
    }

    // 日付から年度の数字を出す（2027-03-31 → 2026、2027-04-01 → 2027）
    public static function yearOf(CarbonInterface $date): int
    {
        return $date->month >= 4 ? $date->year : $date->year - 1;
    }

    // 終わった年度か（終了日が今日より前）
    public function isPast(): bool
    {
        return $this->end_date->lt(today());
    }

    // 変更してよい年度か
    // 今年度とこれからの年度はいつでも変更できる。過去の年度は、管理者が編集を許可しているあいだだけ
    public function isEditable(): bool
    {
        return ! $this->isPast() || $this->unlocked_at !== null;
    }

    // 読み取り専用の年度だけに絞る（使い方：AcademicYear::locked()->get()）
    /** @param  Builder<AcademicYear>  $query */
    public function scopeLocked(Builder $query): void
    {
        $query->whereDate('end_date', '<', today())->whereNull('unlocked_at');
    }

    // 画面表示用：「2027年度」（$year->label で使える）
    /** @return Attribute<string, never> */
    protected function label(): Attribute
    {
        return Attribute::make(get: fn () => "{$this->year}年度");
    }
}