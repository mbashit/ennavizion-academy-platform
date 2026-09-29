<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = ['type', 'data', 'status'];

    protected $casts = [
        'data' => 'array',
    ];
}
