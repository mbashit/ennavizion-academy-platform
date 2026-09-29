<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpactStat extends Model
{
    protected $fillable = ['value', 'label', 'is_confirmed', 'sort_order'];

    protected $casts = [
        'is_confirmed' => 'boolean',
        'sort_order' => 'integer',
    ];
}
