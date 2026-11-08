<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

// 教室（例：301教室・3階）
class Classroom extends Model
{
    protected $fillable = ['name', 'floor'];

    protected function casts(): array
    {
        return ['floor' => 'integer'];
    }

    // この教室に置いてあるカメラ（パソコン）。まだ登録していなければ null
    /** @return HasOne<Camera, $this> */
    public function camera(): HasOne
    {
        return $this->hasOne(Camera::class);
    }

    // 画面表示用：「3階」「地下1階」（$classroom->floor_label で使える）
    /** @return Attribute<string, never> */
    protected function floorLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->floor < 0
            ? '地下'.abs($this->floor).'階'
            : "{$this->floor}階");
    }
}