<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A programme can be published before its fee is agreed; the site then shows
// "Fees on request" instead of a number.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedInteger('fee')->nullable()->change();
            $table->string('period')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedInteger('fee')->nullable(false)->default(0)->change();
            $table->string('period')->nullable(false)->default('per month')->change();
        });
    }
};
