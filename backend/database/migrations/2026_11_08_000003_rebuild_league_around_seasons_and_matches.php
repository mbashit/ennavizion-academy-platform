<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Before: a hand-typed table (played/won/drawn/... per team) plus separate fixtures and
 * results tables — three copies of the same facts that could disagree.
 *
 * After:
 *   league_seasons      name, age group, points rules and tie-breakers, which one is current
 *   league_season_team  which teams play in a season
 *   league_matches      one row per match: a fixture until it has a score, a result after.
 *                       Only published matches appear on the site; only published, played
 *                       matches count toward the table, which is always calculated.
 * Existing data is carried over into a season named after the old "league_season" setting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('age_group')->nullable();
            $table->unsignedTinyInteger('points_win')->default(3);
            $table->unsignedTinyInteger('points_draw')->default(1);
            $table->unsignedTinyInteger('points_loss')->default(0);
            $table->json('tiebreakers')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('league_season_team', function (Blueprint $table) {
            $table->foreignId('league_season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_team_id')->constrained()->cascadeOnDelete();
            $table->primary(['league_season_id', 'league_team_id']);
        });

        Schema::create('league_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_season_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('venue')->nullable();
            $table->foreignId('home_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->string('status', 16)->default('scheduled'); // scheduled | played | postponed | cancelled
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['league_season_id', 'status', 'is_published']);
        });

        // Carry over existing teams, fixtures and results.
        $hasOld = Schema::hasTable('league_fixtures') && Schema::hasTable('league_results');
        if ($hasOld && (DB::table('league_teams')->exists())) {
            $now = now();
            $seasonName = DB::table('site_settings')->where('key', 'league_season')->value('value') ?: 'Current season';
            $seasonId = DB::table('league_seasons')->insertGetId([
                'name' => $seasonName, 'tiebreakers' => json_encode(['goal_difference', 'goals_for']),
                'is_current' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (DB::table('league_teams')->pluck('id') as $teamId) {
                DB::table('league_season_team')->insert(['league_season_id' => $seasonId, 'league_team_id' => $teamId]);
            }
            foreach (DB::table('league_fixtures')->get() as $f) {
                DB::table('league_matches')->insert([
                    'league_season_id' => $seasonId, 'date' => $f->date, 'time' => $f->time, 'venue' => $f->venue,
                    'home_team_id' => $f->home_team_id, 'away_team_id' => $f->away_team_id,
                    'status' => 'scheduled', 'is_published' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            foreach (DB::table('league_results')->get() as $r) {
                DB::table('league_matches')->insert([
                    'league_season_id' => $seasonId, 'date' => $r->date,
                    'home_team_id' => $r->home_team_id, 'away_team_id' => $r->away_team_id,
                    'home_score' => $r->home_score, 'away_score' => $r->away_score,
                    'status' => 'played', 'is_published' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        Schema::dropIfExists('league_fixtures');
        Schema::dropIfExists('league_results');
        Schema::table('league_teams', function (Blueprint $table) {
            $table->dropColumn(['played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against']);
        });
        DB::table('site_settings')->where('key', 'league_season')->delete();
    }

    public function down(): void
    {
        Schema::table('league_teams', function (Blueprint $table) {
            foreach (['played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against'] as $col) {
                $table->unsignedInteger($col)->default(0);
            }
        });
        Schema::create('league_fixtures', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->foreignId('home_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->string('venue');
            $table->timestamps();
        });
        Schema::create('league_results', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('home_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->unsignedInteger('home_score');
            $table->foreignId('away_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->unsignedInteger('away_score');
            $table->timestamps();
        });
        Schema::dropIfExists('league_matches');
        Schema::dropIfExists('league_season_team');
        Schema::dropIfExists('league_seasons');
    }
};
