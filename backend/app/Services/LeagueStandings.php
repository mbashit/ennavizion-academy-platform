<?php

namespace App\Services;

use App\Models\LeagueMatch;
use App\Models\LeagueSeason;

/**
 * Calculates a season's table from its published, played matches using that season's
 * points and tie-breaker rules. Teams registered for the season appear even before they
 * have played. Final tie-breaker is the team name, so the order is always stable.
 */
class LeagueStandings
{
    /**
     * @return list<array{team: string, us: bool, p: int, w: int, d: int, l: int, gf: int, ga: int, gd: int, pts: int}>
     */
    public function for(LeagueSeason $season): array
    {
        $rows = [];
        $row = function ($team) use (&$rows) {
            return $rows[$team->id] ??= [
                'team' => $team->name, 'us' => (bool) $team->is_ennavizion,
                'p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0,
            ];
        };

        foreach ($season->teams as $team) {
            $row($team);
        }

        $matches = LeagueMatch::with('homeTeam', 'awayTeam')
            ->where('league_season_id', $season->id)->countedResults()->get();

        foreach ($matches as $m) {
            $row($m->homeTeam);
            $row($m->awayTeam);
            $h = &$rows[$m->home_team_id];
            $a = &$rows[$m->away_team_id];
            $h['p']++; $a['p']++;
            $h['gf'] += $m->home_score; $h['ga'] += $m->away_score;
            $a['gf'] += $m->away_score; $a['ga'] += $m->home_score;
            if ($m->home_score > $m->away_score) { $h['w']++; $a['l']++; }
            elseif ($m->home_score < $m->away_score) { $a['w']++; $h['l']++; }
            else { $h['d']++; $a['d']++; }
            unset($h, $a);
        }

        $rows = array_map(fn ($r) => $r + [
            'gd' => $r['gf'] - $r['ga'],
            'pts' => $r['w'] * $season->points_win + $r['d'] * $season->points_draw + $r['l'] * $season->points_loss,
        ], array_values($rows));

        $key = [
            'goal_difference' => fn ($r) => $r['gd'],
            'goals_for' => fn ($r) => $r['gf'],
            'wins' => fn ($r) => $r['w'],
        ];
        $order = $season->tiebreakerOrder();

        usort($rows, function ($x, $y) use ($key, $order) {
            if ($c = $y['pts'] <=> $x['pts']) {
                return $c;
            }
            foreach ($order as $t) {
                if ($c = $key[$t]($y) <=> $key[$t]($x)) {
                    return $c;
                }
            }

            return strcasecmp($x['team'], $y['team']);
        });

        return $rows;
    }
}
