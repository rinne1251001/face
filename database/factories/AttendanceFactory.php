<?php

namespace Database\Factories;

use App\Models\Attendance;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

// 出席記録（1人 × 1授業 × 1日 × 1時限で1件）
/** @extends Factory<Attendance> */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'student_id' => StudentFactory::new(),
            'subject_id' => SubjectFactory::new(),
            'date' => today()->toDateString(),
            'period' => 1,
            'status' => 'present',
            'method' => 'face',
            'match_log_id' => null,
            'teacher_id' => null,
        ];
    }

    // 状態（present：出席 / late：遅刻 / excused：公欠 / absent：欠席）
    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }

    // 顔認証で記録した（$matchLogId … そのときの照合ログ）
    public function byFace(?int $matchLogId = null): static
    {
        return $this->state(['method' => 'face', 'match_log_id' => $matchLogId, 'teacher_id' => null]);
    }

    // 教師が手動で記録した
    public function manual(?int $teacherId): static
    {
        return $this->state(['method' => 'manual', 'match_log_id' => null, 'teacher_id' => $teacherId]);
    }

    // 記録した時刻（画面の「記録方法」の横に出る時刻は updated_at）
    public function at(CarbonInterface $time): static
    {
        return $this->state(['created_at' => $time->format('Y-m-d H:i:s'), 'updated_at' => $time->format('Y-m-d H:i:s')]);
    }
}