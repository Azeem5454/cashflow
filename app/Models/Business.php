<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Business extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'currency',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function entries(): HasManyThrough
    {
        return $this->hasManyThrough(Entry::class, Book::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Short currency symbol for display (e.g. "Rs ", "$", "€").
     */
    public function currencySymbol(): string
    {
        return match($this->currency) {
            'PKR'  => 'Rs ',
            'USD'  => '$',
            'EUR'  => '€',
            'GBP'  => '£',
            'AED'  => 'AED ',
            'SAR'  => 'SR ',
            'INR'  => '₹',
            'BDT'  => '৳',
            'CAD'  => 'CA$',
            'AUD'  => 'A$',
            default => $this->currency . ' ',
        };
    }

    /**
     * Whether this business has Pro features unlocked.
     * Determined by the owner's plan — not the logged-in user's.
     * Editors/viewers on a Pro owner's business can use Pro features.
     */
    public function isPro(): bool
    {
        return $this->owner->isPro();
    }

    public function userRole(User $user): ?string
    {
        $member = $this->members()->where('users.id', $user->id)->first();
        return $member?->pivot->role;
    }
}
