<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = ['name', 'furigana', 'student_number', 'department_id', 'is_active'];

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

    // 学科（必ず1つ。何年で卒業するかは学科で決まる）
    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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

    // 在籍（年度ごとに1件。過去の年度の分も残る）
    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
    
    // ある年度の在籍（その年度に在籍していなければ null）
    // enrollments を読み込み済みならそれを使うので、一覧で with() しておけば SQL は増えない
    public function enrollmentFor(AcademicYear $year): ?Enrollment
    {
        return $this->enrollments->firstWhere('academic_year_id', $year->id);
    }

    // 今年度の在籍（今年度に在籍していなければ null）
    // 今のクラスは $student->currentEnrollment?->schoolClass、出席番号は ->attend_number
    /** @return HasOne<Enrollment, $this> */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)->where('academic_year_id', AcademicYear::current()?->id);
    }
}