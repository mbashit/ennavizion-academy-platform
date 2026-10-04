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
