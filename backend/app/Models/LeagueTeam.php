<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LeagueTeam extends Model
{
    protected $fillable = [
        'name', 'played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against', 'is_ennavizion',
    ];

    protected $casts = [
        'played' => 'integer',
        'won' => 'integer',
        'drawn' => 'integer',
        'lost' => 'integer',
        'goals_for' => 'integer',
        'goals_against' => 'integer',
        'is_ennavizion' => 'boolean',
    ];

    // Matches the points/goal-difference math already used in js/main.js's leagueRows()
    // (3 for a win, 1 for a draw) — keep both in sync if the rule ever changes.
    protected function points(): Attribute
    {
        return Attribute::get(fn () => $this->won * 3 + $this->drawn);
    }

    protected function goalDifference(): Attribute
    {
        return Attribute::get(fn () => $this->goals_for - $this->goals_against);
    }
}
