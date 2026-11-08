<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One match. It is a fixture while it has no score and a result once it is "played" with
 * both scores entered. Nothing appears on the site until it is published, and only
 * published, played matches count toward the table.
 */
class LeagueMatch extends Model
{
    public const STATUSES = [
        'scheduled' => 'Scheduled',
        'played' => 'Played',
        'postponed' => 'Postponed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'league_season_id', 'date', 'time', 'venue', 'home_team_id', 'away_team_id',
        'home_score', 'away_score', 'status', 'is_published',
    ];

    protected $casts = [
        'date' => 'date',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'is_published' => 'boolean',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(LeagueSeason::class, 'league_season_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(LeagueTeam::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(LeagueTeam::class, 'away_team_id');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    /** Published, played, both scores entered: the matches that count. */
    public function scopeCountedResults(Builder $q): Builder
    {
        return $q->published()->where('status', 'played')->whereNotNull('home_score')->whereNotNull('away_score');
    }

    /** Published matches still to come (including postponed ones with a new date). */
    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->published()->whereIn('status', ['scheduled', 'postponed'])->whereDate('date', '>=', now()->toDateString());
    }
}
