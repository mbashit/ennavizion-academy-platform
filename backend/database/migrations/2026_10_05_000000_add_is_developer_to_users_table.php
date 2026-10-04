<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Developer accounts are hidden from, and untouchable by, the client's own admin
            // users — see UserResource. Only created via `php artisan app:make-developer`.
            $table->boolean('is_developer')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_developer');
        });
    }
};
