<?php

namespace App\Models;

use App\Services\SchoolCalendar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// 変則授業（決まった曜日ではなく、日付を指定して行う授業）
class SpecialClass extends Model
{
    protected $fillable = ['subject_id', 'date', 'period_start', 'period_end', 'note'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'period_start' => 'integer',
            'period_end' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // 変更したら、SchoolCalendar が覚えている内容を捨てる（古い内容で判定しないように）
        static::saved(fn () => app(SchoolCalendar::class)->flush());
        static::deleted(fn () => app(SchoolCalendar::class)->flush());
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // 時限の一覧（例：3〜5限なら [3, 4, 5]）
    /** @return list<int> */
    public function periods(): array
    {
        return range($this->period_start, $this->period_end);
    }
}