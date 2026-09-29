<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_fixtures', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->foreignId('home_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->string('venue');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_fixtures');
    }
};
