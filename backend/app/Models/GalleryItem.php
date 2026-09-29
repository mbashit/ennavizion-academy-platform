<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $fillable = ['image_path', 'category', 'caption', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
