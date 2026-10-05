<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceSample extends Model
{
    protected $fillable = ['student_id', 'image_path', 'embedding', 'dimension', 'model_version'];

    // 特徴量はJSONレスポンスに含めない
    protected $hidden = ['embedding'];

    protected function casts(): array
    {
        return ['embedding' => 'array'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}