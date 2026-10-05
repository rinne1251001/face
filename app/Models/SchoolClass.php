<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// クラス（class は PHP の予約語なので SchoolClass という名前にしている）
class SchoolClass extends Model
{
    protected $fillable = ['name'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    // 受け持ちの教師（0人以上）
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }
}
