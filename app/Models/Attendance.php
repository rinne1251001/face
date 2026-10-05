<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// 出席記録（1人の生徒 × 1つの授業 × 1日 で1件）
class Attendance extends Model
{
    // 出席の状態（DBに入る値 => 画面に出す名前）
    public const STATUSES = [
        'present' => '出席',
        'late' => '遅刻',
        'absent' => '欠席',
    ];

    // 記録の方法
    public const METHODS = [
        'face' => '顔認証',
        'manual' => '手動',
    ];

    // date は 'Y-m-d' の文字列のまま扱う（DB の date 型と比べやすくするため、日付型への変換はしない）
    protected $fillable = ['student_id', 'subject_id', 'date', 'status', 'method', 'match_log_id', 'teacher_id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // 手動で登録した教師（顔認証のときは null）
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
