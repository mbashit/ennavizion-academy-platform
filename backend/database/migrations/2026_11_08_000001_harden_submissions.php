<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * - reference: the human-readable number shown to the family/donor (ENR-2026-7KQ4XM) and,
 *   for donations, the Paystack transaction reference.
 * - sensitive: medical/injury notes, encrypted at rest and kept out of the ordinary `data`
 *   JSON so they never appear in lists, exports or notification emails.
 * - fingerprint: a hash used to spot an accidental double-submit of the same application.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('reference', 32)->nullable()->unique()->after('id');
            $table->text('sensitive')->nullable()->after('data');
            $table->string('fingerprint', 64)->nullable()->index()->after('sensitive');
        });

        // Give existing rows a reference; donations keep their Paystack reference.
        $prefix = ['enrolment' => 'ENR', 'contact' => 'MSG', 'donation' => 'DON', 'newsletter' => 'NEW'];
        foreach (DB::table('submissions')->get() as $row) {
            $data = json_decode($row->data, true) ?: [];
            $ref = $data['paystack_reference'] ?? sprintf('%s-%s-%s', $prefix[$row->type] ?? 'SUB',
                substr((string) $row->created_at, 0, 4) ?: date('Y'), Str::upper(Str::random(6)));
            DB::table('submissions')->where('id', $row->id)->update(['reference' => $ref]);
        }
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropIndex(['fingerprint']);
            $table->dropColumn(['reference', 'sensitive', 'fingerprint']);
        });
    }
};
