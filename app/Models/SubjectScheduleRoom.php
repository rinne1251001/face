<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// その曜日の授業の「教室と担当教師」（1行）
//   クラスを選んだ授業 … クラスごとに1行（school_class_id あり）
//   クラスを選ばない授業・「＋追加」した欄 … 教室ごとに1行（school_class_id は null）
class SubjectScheduleRoom extends Model
{
    protected $fillable = ['school_class_id', 'classroom_id', 'teacher_id'];

    protected function casts(): array
    {
        return [
            'subject_schedule_id' => 'integer',
            'school_class_id' => 'integer', // 未設定（null）のときは null のまま
            'classroom_id' => 'integer',
            'teacher_id' => 'integer',
        ];
    }

    // どの曜日の授業か
    /** @return BelongsTo<SubjectSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(SubjectSchedule::class, 'subject_schedule_id');
    }

    // クラス（教室だけの行なら null）
    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    // 授業をする教室（未設定なら null）
    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    // その教室・クラスを担当する教師（未設定なら null）
    /** @return BelongsTo<Teacher, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}