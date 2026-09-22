<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids, Billable, HasRoles, HasApiTokens;

    /**
     * Mass-assignable attributes. NOTE: `plan` and `is_admin` are DELIBERATELY excluded —
     * they must only be set via explicit property assignment after an authorization check
     * (Stripe webhook, admin panel, etc.) to prevent privilege escalation via
     * User::create($request->all()) style calls.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'has_password'      => 'boolean',
        ];
    }

    /**
     * Default for new models (matches the DB default). Social sign-up flips it
     * to false explicitly in SocialAccountService.
     */
    protected $attributes = [
        'has_password' => true,
    ];

    protected static function booted(): void
    {
        // Any time a password is set on an existing account (reset link,
        // profile change, API change) the user now knows a password. The
        // explicit has_password=false set during social sign-up is left alone
        // because it is dirty in the same save.
        static::saving(function (User $user) {
            if ($user->exists && $user->isDirty('password') && ! $user->isDirty('has_password')) {
                $user->has_password = true;
            }
        });
    }

    /**
     * The social provider that manages this account's email ('google' | 'apple'), or null.
     */
    public function authProvider(): ?string
    {
        return in_array($this->provider, ['google', 'apple'], true) ? $this->provider : null;
    }

    public function providerLabel(): ?string
    {
        return match ($this->authProvider()) {
            'google' => 'Google',
            'apple'  => 'Apple',
            default  => null,
        };
    }

    public function isPro(): bool
    {
        return $this->plan === 'pro';
    }

    public function ownedBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'owner_id');
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function roleForBusiness(Business $business): ?string
    {
        $pivot = $this->businesses()->where('businesses.id', $business->id)->first()?->pivot;
        return $pivot?->role;
    }

    public function unreadNotificationsCount(): int
    {
        return $this->unreadNotifications()->count();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\CustomVerifyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\CustomResetPassword($token));
    }
}
