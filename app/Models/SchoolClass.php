<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// クラス（class は PHP の予約語なので SchoolClass という名前にしている）
class SchoolClass extends Model
{
    protected $fillable = ['name', 'classroom_id'];

    protected function casts(): array
    {
        return ['classroom_id' => 'integer']; // 未設定（null）のときは null のまま
    }

    // ホームルームの教室（未設定なら null）
    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    // 受け持ちの教師（0人以上）
    /** @return BelongsToMany<Teacher, $this> */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }
}