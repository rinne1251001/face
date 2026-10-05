<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

// システム管理者
class Admin extends Authenticatable
{
    protected $fillable = ['login_id', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
