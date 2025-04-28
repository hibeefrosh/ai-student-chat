<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'code',
        'instructor',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }
}