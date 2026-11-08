<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Both commands below ask for the password interactively (typing is hidden), so it never
// ends up in shell history or a chat log.

Artisan::command('app:admin-password {email}', function (string $email) {
    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error("No account with the email {$email}.");

        return 1;
    }

    $user->update(['password' => password('New password', required: true, validate: fn ($v) => strlen($v) < 8 ? 'Use at least 8 characters.' : null)]);
    $this->info("Password updated for {$email}.");
})->purpose('Set a new password for an admin panel account');

Artisan::command('app:make-developer', function () {
    $email = text('Email', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email.');
    $user = User::firstOrNew(['email' => $email]);

    if (! $user->exists) {
        $user->name = text('Name', default: 'Developer', required: true);
        $user->password = password('Password', required: true, validate: fn ($v) => strlen($v) < 8 ? 'Use at least 8 characters.' : null);
    }

    $user->is_developer = true; // not mass-assignable on purpose — see User::casts()
    $user->save();

    $this->info($user->wasRecentlyCreated
        ? "Developer account created for {$email}."
        : "{$email} is now a developer account (password unchanged).");
})->purpose('Create a developer account hidden from the client\'s admin users');

// Deletes old form submissions once the academy has decided how long to keep them
// (SUBMISSION_RETENTION_DAYS in .env). Unset = keep everything; nothing is deleted.
// Paid donations are kept regardless — they're financial records.
Artisan::command('app:prune-submissions {--dry-run : Only count what would be deleted}', function () {
    $days = config('privacy.retention_days');
    if (! $days) {
        $this->warn('SUBMISSION_RETENTION_DAYS is not set, so nothing was deleted.');

        return 0;
    }

    $query = \App\Models\Submission::where('created_at', '<', now()->subDays((int) $days))
        ->where(fn ($q) => $q->where('type', '!=', 'donation')->orWhere('status', '!=', 'paid'));
    $count = $query->count();

    if ($this->option('dry-run')) {
        $this->info("{$count} submission(s) older than {$days} days would be deleted.");

        return 0;
    }

    $query->delete();
    \Illuminate\Support\Facades\Log::info('Pruned old submissions', ['count' => $count, 'days' => $days]);
    $this->info("Deleted {$count} submission(s) older than {$days} days.");
})->purpose('Delete form submissions older than the configured retention period');

\Illuminate\Support\Facades\Schedule::command('app:prune-submissions')->daily()
    ->when(fn () => (bool) config('privacy.retention_days'));
