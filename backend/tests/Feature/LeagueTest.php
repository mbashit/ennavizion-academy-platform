<?php

namespace Tests\Feature;

use App\Models\LeagueMatch;
use App\Models\LeagueSeason;
use App\Models\LeagueTeam;
use App\Services\LeagueStandings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueTest extends TestCase
{
    use RefreshDatabase;

    private LeagueSeason $season;
    private array $t = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->season = LeagueSeason::create(['name' => '2026/27 Season', 'is_current' => true, 'tiebreakers' => ['goal_difference', 'goals_for']]);
        foreach (['Ennavizion U17', 'Lagoon Stars', 'Northgate FC', 'Quiet Town'] as $name) {
            $this->t[$name] = LeagueTeam::create(['name' => $name, 'is_ennavizion' => $name === 'Ennavizion U17']);
        }
        $this->season->teams()->attach(collect($this->t)->pluck('id'));
    }

    private function match(string $home, int|null $hs, string $away, int|null $as, string $status = 'played', bool $published = true, ?string $date = null): LeagueMatch
    {
        return LeagueMatch::create([
            'league_season_id' => $this->season->id, 'date' => $date ?? '2026-10-03',
            'home_team_id' => $this->t[$home]->id, 'away_team_id' => $this->t[$away]->id,
            'home_score' => $hs, 'away_score' => $as, 'status' => $status, 'is_published' => $published,
        ]);
    }

    private function table(): array
    {
        return app(LeagueStandings::class)->for($this->season->fresh('teams'));
    }

    public function test_table_is_calculated_from_published_played_matches_only(): void
    {
        $this->match('Ennavizion U17', 3, 'Lagoon Stars', 0);
        $this->match('Northgate FC', 1, 'Lagoon Stars', 1);
        $this->match('Northgate FC', 5, 'Ennavizion U17', 0, published: false);   // not approved yet
        $this->match('Lagoon Stars', null, 'Quiet Town', null, status: 'scheduled'); // fixture

        $t = collect($this->table())->keyBy('team');
        $this->assertSame(['p' => 1, 'w' => 1, 'd' => 0, 'l' => 0, 'gf' => 3, 'ga' => 0, 'gd' => 3, 'pts' => 3],
            array_intersect_key($t['Ennavizion U17'], array_flip(['p', 'w', 'd', 'l', 'gf', 'ga', 'gd', 'pts'])));
        $this->assertSame(1, $t['Northgate FC']['pts']);
        $this->assertSame(2, $t['Lagoon Stars']['p']);
        $this->assertSame(0, $t['Quiet Town']['p'], 'registered teams appear before their first match');
        $this->assertTrue($t['Ennavizion U17']['us']);
    }

    public function test_points_rules_are_configurable_per_season(): void
    {
        $this->season->update(['points_win' => 2, 'points_draw' => 1, 'points_loss' => 0]);
        $this->match('Ennavizion U17', 1, 'Lagoon Stars', 0);
        $this->assertSame(2, collect($this->table())->firstWhere('team', 'Ennavizion U17')['pts']);
    }

    public function test_tiebreaker_order_is_configurable(): void
    {
        // Both on 3 points. Lagoon: GD +1, GF 4. Northgate: GD +2, GF 2.
        $this->match('Lagoon Stars', 4, 'Quiet Town', 3);
        $this->match('Northgate FC', 2, 'Quiet Town', 0, date: '2026-10-04');

        $this->assertSame('Northgate FC', $this->table()[0]['team']); // goal difference first
        $this->season->update(['tiebreakers' => ['goals_for', 'goal_difference']]);
        $this->assertSame('Lagoon Stars', $this->table()[0]['team']); // goals scored first
    }

    public function test_public_content_serves_the_current_season(): void
    {
        $this->match('Ennavizion U17', 2, 'Lagoon Stars', 1);
        $this->match('Northgate FC', null, 'Quiet Town', null, status: 'scheduled', date: now()->addWeek()->toDateString());
        $this->match('Lagoon Stars', null, 'Northgate FC', null, status: 'scheduled', published: false, date: now()->addWeek()->toDateString());

        $league = $this->getJson('/api/content')->assertOk()->json('LEAGUE');
        $this->assertSame('2026/27 Season', $league['season']);
        $this->assertSame('Ennavizion U17', $league['table'][0]['team']);
        $this->assertCount(1, $league['results']);
        $this->assertCount(1, $league['fixtures'], 'unpublished fixtures stay hidden');
        $this->assertSame(['win' => 3, 'draw' => 1, 'loss' => 0, 'tiebreakers' => ['goal_difference', 'goals_for']], $league['rules']);
    }

    public function test_no_current_season_gives_empty_league(): void
    {
        $this->season->update(['is_current' => false]);
        $this->getJson('/api/content')->assertJsonPath('LEAGUE.table', [])->assertJsonPath('LEAGUE.fixtures', []);
    }

    public function test_only_one_season_is_current(): void
    {
        LeagueSeason::create(['name' => 'Next', 'is_current' => true]);
        $this->assertFalse($this->season->fresh()->is_current);
    }

    public function test_league_data_cannot_be_changed_through_the_public_api(): void
    {
        $this->postJson('/api/content', ['LEAGUE' => []])->assertStatus(405);
        $this->get('/admin/league-matches')->assertRedirect(); // admin requires login
    }
}
