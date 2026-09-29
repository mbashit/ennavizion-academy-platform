<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    protected $fillable = ['name', 'pitch', 'days', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
