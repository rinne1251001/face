<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 入校証（FeliCa）。student_id が null のカードは、まだ誰のものでもない
class StudentCard extends Model
{
    protected $fillable = ['student_id', 'idm', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // 持ち主の生徒（まだ決まっていなければ null）
    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // このカードをタッチした記録
    /** @return HasMany<CardRead, $this> */
    public function reads(): HasMany
    {
        return $this->hasMany(CardRead::class);
    }

    // 本人確認に使えるカードだけ（使用中 かつ 持ち主が決まっている）
    // 使い方：StudentCard::usable()->where('idm', $idm)->first()
    /** @param Builder<StudentCard> $query */
    public function scopeUsable(Builder $query): void
    {
        $query->where('is_active', true)->whereNotNull('student_id');
    }

    // IDm を「大文字の16進数16桁」にそろえる（リーダーによって小文字・空白・コロン入りで届くことがあるため）
    public static function normalizeIdm(?string $idm): ?string
    {
        $idm = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', (string) $idm));

        return $idm === '' ? null : $idm;
    }

    // 保存するときは、必ずそろえた形にする
    // （読むときはそのまま：Attribute<読むときの型, 保存するときに受け取る型>）
    /** @return Attribute<string, string|null> */
    protected function idm(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => self::normalizeIdm($value));
    }
}