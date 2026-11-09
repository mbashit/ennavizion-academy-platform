<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * One table, two kinds of account:
 *   staff  — created by an existing admin or on the server; use the admin panel (/admin).
 *   parent — sign up themselves on the parent portal (/portal) and verify their email.
 * Each kind can only enter its own panel. `role` and `is_developer` are deliberately not
 * mass-assignable, so no form can change them.
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    public const STAFF = 'staff';
    public const PARENT = 'parent';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'phone'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['role' => self::STAFF];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Deliberately not $fillable: no admin form can grant it, only app:make-developer.
            'is_developer' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            'portal' => $this->isParent(),
            default => false,
        };
    }

    public function isStaff(): bool
    {
        return $this->role === self::STAFF;
    }

    public function isParent(): bool
    {
        return $this->role === self::PARENT;
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function trialApplications(): HasMany
    {
        return $this->hasMany(TrialApplication::class);
    }
}
