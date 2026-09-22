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

    /** Free plan: total members allowed, owner included. Pro is unlimited. */
    public const FREE_MEMBER_LIMIT = 2;

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

    /** Max members (owner included) for this business's plan, or null when unlimited. */
    public function memberLimit(): ?int
    {
        return $this->isPro() ? null : self::FREE_MEMBER_LIMIT;
    }

    /** Invitations that are still open (not accepted, not expired) — each holds a seat. */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    /** Seats taken on this plan: members (owner included) + open invitations. */
    public function seatsUsed(): int
    {
        return $this->members()->count() + $this->pendingInvitations()->count();
    }

    /**
     * Can the owner send an invitation to $email under the plan's seat limit?
     * Re-sending an invitation that is already open doesn't take a new seat.
     */
    public function canInvite(string $email): bool
    {
        $limit = $this->memberLimit();
        if ($limit === null) {
            return true;
        }

        $alreadyInvited = $this->pendingInvitations()
            ->whereRaw('LOWER(email) = ?', [\Illuminate\Support\Str::lower(trim($email))])
            ->exists();

        return $alreadyInvited || $this->seatsUsed() < $limit;
    }

    /**
     * Net balance (opening balances + cash in − cash out) across all books,
     * keyed by business id, in two queries for any number of businesses.
     *
     * @param  array<int, string>  $businessIds
     * @return array<string, string> formatted to 2 decimals
     */
    public static function netBalances(array $businessIds): array
    {
        if ($businessIds === []) {
            return [];
        }

        $openings = \Illuminate\Support\Facades\DB::table('books')
            ->whereIn('business_id', $businessIds)
            ->groupBy('business_id')
            ->selectRaw('business_id, COALESCE(SUM(opening_balance), 0) AS total')
            ->pluck('total', 'business_id');

        $movements = \Illuminate\Support\Facades\DB::table('entries')
            ->join('books', 'books.id', '=', 'entries.book_id')
            ->whereIn('books.business_id', $businessIds)
            ->groupBy('books.business_id')
            ->selectRaw("books.business_id, COALESCE(SUM(CASE WHEN entries.type = 'in' THEN entries.amount ELSE -entries.amount END), 0) AS total")
            ->pluck('total', 'business_id');

        // Postgres returns exact decimal strings; SQLite may return floats
        // (possibly in exponent form), which bcmath can't parse.
        $dec = static fn ($v): string => is_string($v) && preg_match('/^-?\d+(\.\d+)?$/', $v)
            ? $v
            : number_format((float) $v, 2, '.', '');

        $out = [];
        foreach ($businessIds as $id) {
            $out[$id] = bcadd($dec($openings[$id] ?? '0'), $dec($movements[$id] ?? '0'), 2);
        }

        return $out;
    }

    public function userRole(User $user): ?string
    {
        $member = $this->members()->where('users.id', $user->id)->first();
        return $member?->pivot->role;
    }
}
