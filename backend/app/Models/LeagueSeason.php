<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A league season with its own competition rules. Exactly one season is "current"; that is
 * the one the public site shows. Saving a season as current un-sets the others.
 */
class LeagueSeason extends Model
{
    /** Tie-breakers the standings calculator understands, in the admin's chosen order. */
    public const TIEBREAKERS = [
        'goal_difference' => 'Goal difference',
        'goals_for' => 'Goals scored',
        'wins' => 'Wins',
    ];

    protected $fillable = ['name', 'age_group', 'points_win', 'points_draw', 'points_loss', 'tiebreakers', 'is_current'];

    protected $casts = [
        'points_win' => 'integer',
        'points_draw' => 'integer',
        'points_loss' => 'integer',
        'tiebreakers' => 'array',
        'is_current' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (LeagueSeason $season) {
            if ($season->is_current) {
                static::whereKeyNot($season->getKey())->where('is_current', true)->update(['is_current' => false]);
            }
        });
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(LeagueTeam::class, 'league_season_team');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(LeagueMatch::class);
    }

    /** @return list<string> */
    public function tiebreakerOrder(): array
    {
        return array_values(array_filter($this->tiebreakers ?? ['goal_difference', 'goals_for'],
            fn ($t) => array_key_exists($t, self::TIEBREAKERS)));
    }
}
