<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

// 顔の照合ログ（照合するたびに1行。created_at だけ使う）
class MatchLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['student_id', 'similarity', 'margin', 'accepted', 'model_version', 'teacher_id'];

    protected function casts(): array
    {
        return ['accepted' => 'boolean'];
    }

    // 最も近かった生徒（該当者がいない・生徒が削除されたときは null）
    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // 照合した教師（管理者の照合テストのときは null）
    /** @return BelongsTo<Teacher, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    // この照合で記録した出席（出席にならなかった照合なら null）
    /** @return HasOne<Attendance, $this> */
    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }
}