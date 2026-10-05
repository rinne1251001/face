<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['student_id', 'similarity', 'margin', 'accepted', 'model_version', 'teacher_id'];

    protected function casts(): array
    {
        return ['accepted' => 'boolean'];
    }
}