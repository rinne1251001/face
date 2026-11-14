<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 学科（years ＝ 修業年限。2年制なら 2 で、2年生を終えたら卒業）
class Department extends Model
{
    protected $fillable = ['name', 'years'];

    protected function casts(): array
    {
        return ['years' => 'integer'];
    }

    // 選べる学年の最大（学科を指定すればその学科の修業年限、しなければいちばん長い学科。学科がまだなければ 4）
    public static function maxYears(?int $departmentId = null): int
    {
        $years = $departmentId ? static::query()->whereKey($departmentId)->value('years') : null;

        return (int) ($years ?: static::query()->max('years') ?: 4);
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}