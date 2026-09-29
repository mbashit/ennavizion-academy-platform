<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['sku', 'name', 'price', 'tag', 'in_stock', 'colors', 'image_path', 'sort_order'];

    protected $casts = [
        'price' => 'integer',
        'in_stock' => 'boolean',
        'colors' => 'array',
        'sort_order' => 'integer',
    ];
}
