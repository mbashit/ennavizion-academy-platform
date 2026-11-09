<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent portal.
 *   users.role          staff (admin panel) | parent (parent portal). Existing accounts are staff.
 *   players             a parent's children; medical notes encrypted at rest.
 *   trial_applications  a parent's application for one player, with the status and trial
 *                       details staff set in the admin panel. staff_notes never reach parents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 16)->default('staff')->after('email')->index();
            $table->string('phone', 30)->nullable()->after('role');
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the parent/guardian
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('dob');
            $table->string('position', 30)->nullable();
            $table->string('current_club')->nullable();
            $table->text('medical_notes')->nullable(); // encrypted cast
            $table->timestamps();
        });

        Schema::create('trial_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('program_name'); // kept even if the programme is later renamed/deleted
            $table->string('centre')->nullable();
            $table->text('parent_note')->nullable();
            $table->string('status', 24)->default('submitted')->index();
            $table->dateTime('trial_at')->nullable();
            $table->string('trial_location')->nullable();
            $table->text('message_to_parent')->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamp('consent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_applications');
        Schema::dropIfExists('players');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'phone']);
        });
    }
};
