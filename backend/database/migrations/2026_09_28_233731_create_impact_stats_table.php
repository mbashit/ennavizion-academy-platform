<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impact_stats', function (Blueprint $table) {
            $table->id();
            $table->string('value');
            $table->string('label');
            // Defaults to false: every stat starts as an unconfirmed placeholder (e.g.
            // "XX+"). Flip to true only once a real, client-confirmed figure replaces it —
            // the public API (see IMPACT wiring, once built) can then choose whether to
            // show unconfirmed placeholders at all.
            $table->boolean('is_confirmed')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impact_stats');
    }
};
