<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['name', 'furigana', 'student_number', 'attend_number', 'school_class_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // 所属クラス（必ず1つ）
    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    // 受講する授業（1つ以上）
    /** @return BelongsToMany<Subject, $this> */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    /** @return HasMany<FaceSample, $this> */
    public function faceSamples(): HasMany
    {
        return $this->hasMany(FaceSample::class);
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // 入校証（紛失したものも含む）
    /** @return HasMany<StudentCard, $this> */
    public function cards(): HasMany
    {
        return $this->hasMany(StudentCard::class);
    }

    // 入校証をタッチした記録
    /** @return HasMany<CardRead, $this> */
    public function cardReads(): HasMany
    {
        return $this->hasMany(CardRead::class);
    }
}