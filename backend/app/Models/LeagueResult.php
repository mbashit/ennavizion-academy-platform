<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueResult extends Model
{
    protected $fillable = ['date', 'home_team_id', 'home_score', 'away_team_id', 'away_score'];

    protected $casts = [
        'date' => 'date',
    ];

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(LeagueTeam::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(LeagueTeam::class, 'away_team_id');
    }
}
