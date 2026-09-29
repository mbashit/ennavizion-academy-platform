<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = ['slug', 'name', 'ages', 'fee', 'period', 'description', 'sort_order'];

    protected $casts = [
        'fee' => 'integer',
        'sort_order' => 'integer',
    ];
}
