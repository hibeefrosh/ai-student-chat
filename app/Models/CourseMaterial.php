<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'file_path',
        'file_type',
        'file_size',
        'is_processed',
        'content_text',
        'embeddings_status',
    ];

    protected $casts = [
        'is_processed' => 'boolean',
        'embeddings_status' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(MaterialChunk::class);
    }
}