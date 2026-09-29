<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'slug', 'title', 'published_at', 'category', 'minutes',
        'excerpt', 'body', 'image_path', 'image_alt', 'is_published',
    ];

    protected $casts = [
        'published_at' => 'date',
        'minutes' => 'integer',
        'is_published' => 'boolean',
    ];
}
