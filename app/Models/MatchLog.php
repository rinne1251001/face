<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['person_id', 'similarity', 'margin', 'accepted', 'model_version', 'admin_id'];

    protected function casts(): array
    {
        return ['accepted' => 'boolean'];
    }
}