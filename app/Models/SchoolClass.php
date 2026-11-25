<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// クラス（class は PHP の予約語なので SchoolClass という名前にしている）
class SchoolClass extends Model
{
    protected $fillable = ['academic_year_id', 'name', 'grade', 'classroom_id'];

    protected function casts(): array
    {
        return [
            'academic_year_id' => 'integer',
            'grade' => 'integer',
            'classroom_id' => 'integer', // 未設定（null）のときは null のまま
        ];
    }

    // ホームルームの教室（未設定なら null）
    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    // どの年度のクラスか
    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // 受け持ちの教師（0人以上）
    /** @return BelongsToMany<Teacher, $this> */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }

    // クラスの生徒（enrollments 表を中間テーブルとして使う）
    // 出席番号は $student->pivot->attend_number で取れる
    /** @return BelongsToMany<Student, $this> */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')->withPivot('attend_number', 'grade');
    }

    // 在籍（このクラスに決まった生徒の分）
    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}