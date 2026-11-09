<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A child registered by a parent in the parent portal. */
class Player extends Model
{
    public const POSITIONS = ['Goalkeeper', 'Defender', 'Midfielder', 'Forward', 'Not sure yet'];

    protected $fillable = ['first_name', 'last_name', 'dob', 'position', 'current_club', 'medical_notes'];

    protected $hidden = ['medical_notes'];

    protected $casts = [
        'dob' => 'date',
        'medical_notes' => 'encrypted',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TrialApplication::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->dob?->age);
    }
}
