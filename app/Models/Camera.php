<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

// 教室に置きっぱなしにするカメラ（パソコン）
// 管理者・教師と同じようにログインする（教室を選んでパスワードを入れる）。ログインしたまま照合に使う
class Camera extends Authenticatable
{
    protected $fillable = ['classroom_id', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'classroom_id' => 'integer',
            'password' => 'hashed', // 保存するときに自動でハッシュ化する
        ];
    }

    // 置いてある教室
    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}