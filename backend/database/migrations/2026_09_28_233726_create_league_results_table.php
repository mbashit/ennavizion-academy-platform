<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_results', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('home_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->unsignedInteger('home_score');
            $table->foreignId('away_team_id')->constrained('league_teams')->cascadeOnDelete();
            $table->unsignedInteger('away_score');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_results');
    }
};
