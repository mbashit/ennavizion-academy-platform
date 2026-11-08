<?php

namespace Tests\Feature;

use App\Models\LeagueMatch;
use App\Models\LeagueSeason;
use App\Models\LeagueTeam;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Every admin screen renders for signed-in staff, and none is reachable without signing in. */
class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_screens_render_for_staff_and_require_login(): void
    {
        $season = LeagueSeason::create(['name' => 'Test season', 'is_current' => true]);
        [$a, $b] = [LeagueTeam::create(['name' => 'Alpha']), LeagueTeam::create(['name' => 'Beta'])];
        $season->teams()->attach([$a->id, $b->id]);
        $match = LeagueMatch::create(['league_season_id' => $season->id, 'date' => '2026-10-03', 'home_team_id' => $a->id,
            'away_team_id' => $b->id, 'home_score' => 2, 'away_score' => 1, 'status' => 'played', 'is_published' => true]);
        $sub = Submission::create(['type' => 'enrolment', 'status' => 'new',
            'data' => ['player_first' => 'Ada', 'program' => 'Development', 'guardian_name' => 'G', 'consent' => true],
            'sensitive' => ['medical_notes' => 'Asthma']]);

        $urls = [
            '/admin', '/admin/submissions', "/admin/submissions/{$sub->id}/edit",
            '/admin/league-seasons', '/admin/league-seasons/create', "/admin/league-seasons/{$season->id}/edit",
            '/admin/league-matches', '/admin/league-matches/create', "/admin/league-matches/{$match->id}/edit",
            '/admin/league-teams', '/admin/programs', '/admin/programs/create', '/admin/settings', '/admin/contributions',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(); // to the login page
        }

        $this->actingAs(User::factory()->create());
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        // The submission screen shows the decrypted notes to staff; the list does not.
        $this->get("/admin/submissions/{$sub->id}/edit")->assertSee('Asthma')->assertSee($sub->reference);
        $this->get('/admin/submissions')->assertDontSee('Asthma')->assertDontSee('Ada');
        // Searching and filtering contributions works (Filament injects closure arguments by name).
        $this->get('/admin/contributions?tableSearch=Ada&tableFilters[mode][value]=test')->assertOk();
        // The season screen previews the calculated table.
        $this->get("/admin/league-seasons/{$season->id}/edit")->assertSee('Alpha');
    }
}
