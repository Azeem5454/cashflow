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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids, Billable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'plan',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
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
}
