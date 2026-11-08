<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['mail.default' => 'array', 'services.notify.email' => 'staff@example.com']);
    }

    private function application(array $overrides = []): array
    {
        return array_merge([
            'type' => 'enrolment',
            'player_first' => 'Ada', 'player_last' => 'Okafor',
            'dob' => now()->subYears(11)->subDays(10)->toDateString(),
            'position' => 'Midfielder', 'program' => 'Development',
            'medical_notes' => 'Mild asthma, carries an inhaler.',
            'guardian_name' => 'Chidi Okafor', 'guardian_phone' => '+234 801 234 5678',
            'guardian_email' => 'chidi@example.com', 'consent' => 'yes',
        ], $overrides);
    }

    public function test_application_is_stored_with_a_reference_and_encrypted_medical_notes(): void
    {
        $res = $this->postJson('/api/submissions', $this->application())->assertCreated();
        $ref = $res->json('reference');
        $this->assertMatchesRegularExpression('/^ENR-\d{4}-[2-9A-HJ-NP-Z]{6}$/', $ref);

        $s = Submission::where('reference', $ref)->firstOrFail();
        $this->assertSame('Mild asthma, carries an inhaler.', $s->sensitive['medical_notes']);
        $this->assertArrayNotHasKey('medical_notes', $s->data);
        $raw = DB::table('submissions')->where('id', $s->id)->value('sensitive');
        $this->assertStringNotContainsString('asthma', $raw, 'medical notes must be encrypted at rest');
        $this->assertTrue($s->data['consent']);
    }

    public function test_staff_email_has_the_reference_but_no_personal_details(): void
    {
        $ref = $this->postJson('/api/submissions', $this->application())->json('reference');
        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $body = $messages[0]->getOriginalMessage()->toString();
        $this->assertStringContainsString($ref, $body);
        foreach (['Ada', 'Okafor', 'asthma', 'chidi@example.com', '801 234'] as $private) {
            $this->assertStringNotContainsString($private, $body);
        }
    }

    public function test_player_outside_the_academy_age_span_is_rejected(): void
    {
        $this->postJson('/api/submissions', $this->application(['dob' => now()->subYears(4)->toDateString(), 'program' => 'Foundation']))
            ->assertUnprocessable()->assertJsonValidationErrors('dob');
        $this->postJson('/api/submissions', $this->application(['dob' => now()->subYears(19)->toDateString(), 'program' => 'Elite Pathway']))
            ->assertUnprocessable()->assertJsonValidationErrors('dob');
    }

    public function test_programme_must_match_the_players_age(): void
    {
        $this->postJson('/api/submissions', $this->application(['program' => 'Foundation'])) // player is 11
            ->assertUnprocessable()->assertJsonValidationErrors('program');
    }

    public function test_unknown_programme_and_missing_consent_are_rejected(): void
    {
        $this->postJson('/api/submissions', $this->application(['program' => 'Made Up', 'consent' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors(['program', 'consent']);
    }

    public function test_spam_trap_submissions_are_not_stored(): void
    {
        $this->postJson('/api/submissions', $this->application(['website' => 'http://spam.example']))->assertCreated();
        $this->assertSame(0, Submission::count());
    }

    public function test_double_submit_returns_the_same_reference_without_a_duplicate(): void
    {
        $first = $this->postJson('/api/submissions', $this->application())->json('reference');
        $second = $this->postJson('/api/submissions', $this->application())->assertOk()->json('reference');
        $this->assertSame($first, $second);
        $this->assertSame(1, Submission::where('type', 'enrolment')->count());
    }

    public function test_contact_message_validation(): void
    {
        $this->postJson('/api/submissions', ['type' => 'contact', 'name' => '<script>', 'email' => 'nope', 'topic' => 'x', 'message' => 'hi'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'topic', 'message']);
        $this->postJson('/api/submissions', ['type' => 'contact', 'name' => 'Bola', 'email' => 'bola@example.com', 'topic' => 'league', 'message' => 'How do we enter a team?'])
            ->assertCreated()->assertJsonPath('reference', fn ($r) => str_starts_with($r, 'MSG-'));
    }

    public function test_newsletter_signup_is_not_duplicated(): void
    {
        $this->postJson('/api/submissions', ['type' => 'newsletter', 'email' => 'fan@example.com'])->assertCreated();
        $this->postJson('/api/submissions', ['type' => 'newsletter', 'email' => 'fan@example.com'])->assertCreated();
        $this->assertSame(1, Submission::where('type', 'newsletter')->count());
    }

    public function test_public_api_never_exposes_submissions(): void
    {
        $this->postJson('/api/submissions', $this->application());
        $content = $this->getJson('/api/content')->assertOk()->getContent();
        $this->assertStringNotContainsString('Okafor', $content);
        $this->get('/admin/submissions')->assertRedirect(); // login required
    }

    public function test_old_submissions_are_pruned_only_when_retention_is_configured(): void
    {
        $old = Submission::create(['type' => 'contact', 'data' => ['name' => 'Old'], 'status' => 'new']);
        $old->forceFill(['created_at' => now()->subDays(400)])->save();
        $paid = Submission::create(['type' => 'donation', 'data' => ['amount' => 5000], 'status' => 'paid']);
        $paid->forceFill(['created_at' => now()->subDays(400)])->save();

        $this->artisan('app:prune-submissions')->assertSuccessful();
        $this->assertSame(2, Submission::count(), 'nothing deleted without a retention period');

        config(['privacy.retention_days' => 365]);
        $this->artisan('app:prune-submissions')->assertSuccessful();
        $this->assertNull(Submission::find($old->id));
        $this->assertNotNull(Submission::find($paid->id), 'paid donations are financial records and are kept');
    }

    public function test_programme_without_a_fee_is_published_as_fees_on_request(): void
    {
        $this->assertNull(Program::where('slug', 'foundation')->value('fee'));
        $this->getJson('/api/content')->assertJsonPath('PROGRAMS.0.fee', null);
    }
}
