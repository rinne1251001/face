<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Teacher extends Authenticatable
{
    protected $fillable = ['login_id', 'password', 'name'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    // 受け持ちクラス（0個以上）
    public function schoolClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class);
    }

    // 担当授業（1つ以上）
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    // この授業を担当しているか（担当外の授業の出席を見たり変えたりできないようにする）
    public function teachesSubject(Subject $subject): bool
    {
        return $this->subjects()->whereKey($subject->id)->exists();
    }

    // このクラスを受け持っているか
    public function hasSchoolClass(SchoolClass $schoolClass): bool
    {
        return $this->schoolClasses()->whereKey($schoolClass->id)->exists();
    }
}
