<?php

namespace App\Models;

use App\Services\SchoolCalendar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

// クラスごとの授業変更（ある日・ある時限について「このクラスは、この授業をやめて、代わりにこの授業をする」）
//   from_subject_id … この枠でやめる授業（null なら、空いている枠への追加）
//   to_subject_id   … この枠で代わりにする授業（null なら休講）
// 移動・入れ替えは2行で1組（group_id が同じ）
class ClassLessonChange extends Model
{
    // 変更の種類
    public const TYPES = [
        'change' => '別の授業に変更',
        'cancel' => '休講',
        'move' => '別の日・時限に移動',
        'swap' => '別の授業と入れ替え',
        'add' => '授業を追加（補講など）',
    ];

    protected $fillable = [
        'group_id', 'type', 'school_class_id', 'date', 'period_start', 'period_end',
        'from_subject_id', 'to_subject_id', 'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'school_class_id' => 'integer',
            'period_start' => 'integer',
            'period_end' => 'integer',
            'from_subject_id' => 'integer',
            'to_subject_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // 変更したら、SchoolCalendar が覚えている内容を捨てる（古い内容で判定しないように）
        static::saved(fn () => app(SchoolCalendar::class)->flush());
        static::deleted(fn () => app(SchoolCalendar::class)->flush());
    }

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function fromSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'from_subject_id');
    }

    /** @return BelongsTo<Subject, $this> */
    public function toSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'to_subject_id');
    }

    // 時限の一覧（例：3〜5限なら [3, 4, 5]）
    /** @return list<int> */
    public function periods(): array
    {
        return range($this->period_start, $this->period_end);
    }

    // この時限を含むか
    public function coversPeriod(int $period): bool
    {
        return $period >= $this->period_start && $period <= $this->period_end;
    }

    // 画面表示用：「11/16（月）1限」「11/18（水）3〜5限」
    public function slotLabel(): string
    {
        $periods = $this->period_start === $this->period_end
            ? "{$this->period_start}限"
            : "{$this->period_start}〜{$this->period_end}限";

        return $this->date->format('n/j').'（'.Subject::DAYS[$this->date->dayOfWeekIso].'）'.$periods;
    }

    // 一覧に出す説明（同じ group_id の行をまとめて1文にする。1行目が元の授業）
    /** @param Collection<int, self> $rows */
    public static function describeGroup(Collection $rows): string
    {
        $first = $rows->first();
        if ($first === null) {
            return '';
        }
        $second = $rows->get(1);
        $class = $first->schoolClass->name;
        $from = $first->fromSubject->name ?? '';
        $to = $first->toSubject->name ?? '';
        $secondSlot = $second?->slotLabel() ?? '?';

        return match ($first->type) {
            'change' => "{$class}：{$first->slotLabel()} {$from} → {$to}",
            'cancel' => "{$class}：{$first->slotLabel()} {$from} → 休講",
            'add' => "{$class}：{$first->slotLabel()} {$to} を追加",
            'move' => "{$class}：{$from} {$first->slotLabel()} → {$secondSlot}",
            'swap' => "{$class}：{$from} {$first->slotLabel()} ⇄ {$to} {$secondSlot}",
            default => '',
        };
    }
}