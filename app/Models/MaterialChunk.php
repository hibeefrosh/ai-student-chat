<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_material_id',
        'content',
        'chunk_index',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function courseMaterial(): BelongsTo
    {
        return $this->belongsTo(CourseMaterial::class);
    }
}