<?php

namespace Tests\Feature;

use App\Filament\Portal\Pages\Auth\Register;
use App\Filament\Portal\Resources\PlayerResource\Pages\CreatePlayer;
use App\Filament\Portal\Resources\PlayerResource\Pages\ListPlayers;
use App\Filament\Portal\Resources\TrialApplicationResource\Pages\CreateTrialApplication;
use App\Filament\Portal\Resources\TrialApplicationResource\Pages\ListTrialApplications;
use App\Filament\Resources\TrialApplicationResource\Pages\EditTrialApplication;
use App\Models\Player;
use App\Models\Program;
use App\Models\TrialApplication;
use App\Models\User;
use App\Notifications\TrialApplicationReceived;
use App\Notifications\TrialApplicationUpdated;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // programmes with age bands
        Notification::fake();
        config(['mail.default' => 'array', 'services.notify.email' => 'staff@example.com']);
    }

    private function parent(string $email = 'parent@example.com'): User
    {
        $u = new User(['name' => 'Ada Parent', 'email' => $email, 'password' => 'secret-password', 'phone' => '+234 801 000 0000']);
        $u->role = User::PARENT;
        $u->email_verified_at = now();
        $u->save();

        return $u;
    }

    private function staff(): User
    {
        return User::factory()->create(); // role defaults to staff
    }

    private function player(User $parent, int $age = 11): Player
    {
        return $parent->players()->create([
            'first_name' => 'Tomi', 'last_name' => 'Parent', 'dob' => now()->subYears($age)->subDays(20)->toDateString(),
            'position' => 'Midfielder', 'medical_notes' => 'Mild asthma',
        ]);
    }

    private function inPortal(User $user): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('portal'));
    }

    public function test_registration_always_creates_a_parent_account(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('portal'));
        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'New Parent', 'email' => 'new@example.com', 'phone' => '+234 802 000 0000',
                'password' => 'a-strong-password', 'passwordConfirmation' => 'a-strong-password',
                'guardian_confirmed' => true, 'role' => 'staff', // a tampered field must be ignored
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $u = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($u->isParent());
        $this->assertSame('+234 802 000 0000', $u->phone);
        $this->assertNull($u->email_verified_at, 'email must be verified before applying');
    }

    public function test_registration_requires_guardian_confirmation(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('portal'));
        Livewire::test(Register::class)
            ->fillForm(['name' => 'X', 'email' => 'x@example.com', 'phone' => '0801 000 0000',
                'password' => 'a-strong-password', 'passwordConfirmation' => 'a-strong-password', 'guardian_confirmed' => false])
            ->call('register')
            ->assertHasFormErrors(['guardian_confirmed']);
        $this->assertNull(User::where('email', 'x@example.com')->first());
    }

    public function test_parents_and_staff_can_only_enter_their_own_panel(): void
    {
        $this->actingAs($this->parent())->get('/admin')->assertForbidden();
        $this->actingAs($this->parent('p2@example.com'))->get('/portal')->assertOk();
        $this->actingAs($this->staff())->get('/portal')->assertForbidden();
        auth()->logout();
        $this->get('/portal')->assertRedirect('/portal/login');
        $this->get('/portal/register')->assertOk();
    }

    public function test_unverified_parent_is_sent_to_verify_their_email(): void
    {
        $u = $this->parent();
        $u->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($u)->get('/portal')->assertRedirect();
        $this->assertFalse($u->can('create', TrialApplication::class));
    }

    public function test_parent_adds_a_player_and_medical_notes_are_encrypted(): void
    {
        $parent = $this->parent();
        $this->inPortal($parent);
        Livewire::test(CreatePlayer::class)
            ->fillForm(['first_name' => 'Kemi', 'last_name' => 'Parent', 'dob' => now()->subYears(8)->toDateString(),
                'position' => 'Forward', 'medical_notes' => 'Peanut allergy'])
            ->call('create')->assertHasNoFormErrors();

        $p = Player::firstOrFail();
        $this->assertSame($parent->id, $p->user_id);
        $this->assertSame('Peanut allergy', $p->medical_notes);
        $this->assertStringNotContainsString('Peanut', DB::table('players')->value('medical_notes'));
    }

    public function test_parents_never_see_another_familys_players_or_applications(): void
    {
        $a = $this->parent('a@example.com'); $b = $this->parent('b@example.com');
        $pa = $this->player($a); $pb = $this->player($b);
        $appB = $b->trialApplications()->create(['player_id' => $pb->id, 'program_name' => 'Development', 'consent_at' => now()]);

        $this->inPortal($a);
        Livewire::test(ListPlayers::class)->assertCanSeeTableRecords([$pa])->assertCanNotSeeTableRecords([$pb]);
        Livewire::test(ListTrialApplications::class)->assertCanNotSeeTableRecords([$appB]);
        $this->get("/portal/players/{$pb->id}/edit")->assertNotFound();
        $this->get("/portal/trial-applications/{$appB->id}")->assertNotFound();
    }

    public function test_apply_for_a_trial_with_age_check_reference_and_emails(): void
    {
        $parent = $this->parent(); $player = $this->player($parent, 11);
        $dev = Program::where('slug', 'development')->first(); $foundation = Program::where('slug', 'foundation')->first();
        $this->inPortal($parent);

        Livewire::test(CreateTrialApplication::class)
            ->fillForm(['player_id' => $player->id, 'program_id' => $foundation->id, 'consent' => true])
            ->call('create')->assertHasFormErrors(['program_id']); // 11-year-old, ages 6–9

        Livewire::test(CreateTrialApplication::class)
            ->fillForm(['player_id' => $player->id, 'program_id' => $dev->id, 'consent' => true, 'parent_note' => 'Left-footed'])
            ->call('create')->assertHasNoFormErrors();

        $app = TrialApplication::firstOrFail();
        $this->assertMatchesRegularExpression('/^ENR-\d{4}-[2-9A-HJ-NP-Z]{6}$/', $app->reference);
        $this->assertSame(['submitted', 'Development', $parent->id], [$app->status, $app->program_name, $app->user_id]);
        Notification::assertSentTo($parent, TrialApplicationReceived::class);
        $staffMail = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $staffMail);
        $this->assertStringNotContainsString('Tomi', $staffMail[0]->getOriginalMessage()->toString());

        Livewire::test(CreateTrialApplication::class)
            ->fillForm(['player_id' => $player->id, 'program_id' => $dev->id, 'consent' => true])
            ->call('create')->assertHasFormErrors(['program_id']); // duplicate open application
    }

    public function test_cannot_apply_for_another_familys_player(): void
    {
        $a = $this->parent('a@example.com'); $b = $this->parent('b@example.com'); $pb = $this->player($b);
        $this->inPortal($a);
        Livewire::test(CreateTrialApplication::class)
            ->fillForm(['player_id' => $pb->id, 'program_id' => Program::where('slug', 'development')->value('id'), 'consent' => true])
            ->call('create')
            ->assertHasFormErrors(['player_id']);
        $this->assertSame(0, TrialApplication::count());
    }

    public function test_staff_update_emails_the_parent_but_internal_notes_do_not(): void
    {
        $parent = $this->parent(); $player = $this->player($parent);
        $app = $parent->trialApplications()->create(['player_id' => $player->id, 'program_name' => 'Development', 'consent_at' => now()]);
        Notification::fake();

        $this->actingAs($this->staff());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(EditTrialApplication::class, ['record' => $app->getRouteKey()])
            ->fillForm(['status' => 'trial_scheduled', 'trial_at' => now()->addWeek()->setTime(10, 0)->toDateTimeString(), 'trial_location' => 'Jos training ground'])
            ->call('save')->assertHasNoFormErrors();
        Notification::assertSentTo($parent, TrialApplicationUpdated::class);

        Notification::fake();
        $app->refresh()->update(['staff_notes' => 'Strong left foot']);
        Notification::assertNothingSent();

        $this->inPortal($parent);
        $this->get("/portal/trial-applications/{$app->id}")->assertOk()
            ->assertSee('Trial scheduled')->assertSee('Jos training ground')->assertDontSee('Strong left foot');
    }

    public function test_scheduling_a_trial_requires_date_and_place(): void
    {
        $parent = $this->parent(); $player = $this->player($parent);
        $app = $parent->trialApplications()->create(['player_id' => $player->id, 'program_name' => 'Development', 'consent_at' => now()]);
        $this->actingAs($this->staff());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(EditTrialApplication::class, ['record' => $app->getRouteKey()])
            ->fillForm(['status' => 'trial_scheduled', 'trial_at' => null, 'trial_location' => null])
            ->call('save')->assertHasFormErrors(['trial_at', 'trial_location']);
    }

    public function test_parent_can_withdraw_an_open_application_only(): void
    {
        $parent = $this->parent(); $player = $this->player($parent);
        $app = $parent->trialApplications()->create(['player_id' => $player->id, 'program_name' => 'Development', 'consent_at' => now()]);
        $this->inPortal($parent);
        Livewire::test(ListTrialApplications::class)->callTableAction('withdraw', $app);
        $this->assertSame('withdrawn', $app->fresh()->status);
        $this->assertFalse($parent->can('withdraw', $app->fresh()));
    }

    public function test_staff_screens_render_and_parents_are_not_admin_users(): void
    {
        $parent = $this->parent(); $player = $this->player($parent);
        $app = $parent->trialApplications()->create(['player_id' => $player->id, 'program_name' => 'Development', 'consent_at' => now()]);
        $this->actingAs($this->staff());
        $this->get('/admin/trial-applications')->assertOk()->assertSee($app->reference);
        $this->get("/admin/trial-applications/{$app->id}/edit")->assertOk()->assertSee('Mild asthma');
        $this->get('/admin/users')->assertOk()->assertDontSee('parent@example.com');
    }

    public function test_portal_dashboard_renders_for_a_parent(): void
    {
        $this->actingAs($this->parent())->get('/portal')->assertOk()->assertSee('Open applications')->assertSee('Next trial');
    }
}
