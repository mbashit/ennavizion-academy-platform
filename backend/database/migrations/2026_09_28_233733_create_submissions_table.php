<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            // Each form (contact/enrolment/donation/newsletter) has a different shape, so
            // the field data lives in one JSON column rather than one wide table with mostly
            // empty columns — mirrors how the old localStorage demo mode stored submissions.
            $table->enum('type', ['contact', 'enrolment', 'donation', 'newsletter']);
            $table->json('data');
            $table->string('status')->default('new'); // new | reviewed | actioned
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
