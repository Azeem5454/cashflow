<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function userRole(User $user): ?string
    {
        $member = $this->members()->where('users.id', $user->id)->first();
        return $member?->pivot->role;
    }
}
