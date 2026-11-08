<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A team that can play in league seasons. Its record is calculated from matches, never stored. */
class LeagueTeam extends Model
{
    protected $fillable = ['name', 'is_ennavizion'];

    protected $casts = [
        'is_ennavizion' => 'boolean',
    ];

    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(LeagueSeason::class, 'league_season_team');
    }
}
