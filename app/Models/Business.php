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
        'contact_phone',
        'contact_email',
    ];

    /**
     * UploadedAsset key holding this business's logo. Not fillable — set only
     * through storeLogo()/removeLogo() so the asset row and the column never
     * drift apart.
     */
    public function logoAssetKey(): string
    {
        return 'business-' . $this->id . '-logo';
    }

    public function hasLogo(): bool
    {
        return $this->logo_key !== null && UploadedAsset::has($this->logo_key);
    }

    /** Public URL for the logo, cache-busted. null when there is none. */
    public function logoUrl(bool $absolute = false): ?string
    {
        if (! $this->hasLogo()) {
            return null;
        }

        return route('brand-asset', $this->logo_key, absolute: $absolute)
            . '?v=' . (UploadedAsset::cacheBuster($this->logo_key) ?? 0);
    }

    /**
     * Store raw image bytes as this business's logo (normalised to PNG,
     * max 512px, EXIF dropped — see BusinessLogo).
     */
    public function storeLogo(string $bytes): void
    {
        $key = $this->logoAssetKey();
        UploadedAsset::put($key, \App\Support\BusinessLogo::normalise($bytes), 'image/png');

        $this->logo_key = $key;
        $this->save();
    }

    public function removeLogo(): void
    {
        if ($this->logo_key) {
            UploadedAsset::forgetKey($this->logo_key);
        }

        $this->logo_key = null;
        $this->save();
    }

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

    /** How many days of daily net the business-row sparkline shows. */
    public const TREND_DAYS = 7;

    /**
     * Compact activity signal for a set of business rows, in ONE query:
     *
     *   trend          last TREND_DAYS daily NET values, oldest → newest
     *                  (cash in − cash out; days with no entries are 0)
     *   monthChangePct this calendar month's net vs last month's, as a signed
     *                  percentage; null when last month had no activity, so
     *                  the UI can stay quiet rather than print a fake ∞%.
     *
     * Each business's figures are in its OWN currency — never summed across
     * businesses.
     *
     * @param  array<int, string>  $businessIds
     * @return array<string, array{trend: array<int, float>, monthChangePct: ?float}>
     */
    public static function trends(array $businessIds): array
    {
        $empty = [
            'trend'          => array_fill(0, self::TREND_DAYS, 0.0),
            'monthChangePct' => null,
        ];

        if ($businessIds === []) {
            return [];
        }

        $today          = \Carbon\Carbon::today();
        $trendStart     = $today->copy()->subDays(self::TREND_DAYS - 1);
        $lastMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $thisMonthStart = $today->copy()->startOfMonth();
        // One window covering both the sparkline and the two month buckets.
        $since = $trendStart->lt($lastMonthStart) ? $trendStart : $lastMonthStart;

        $rows = \Illuminate\Support\Facades\DB::table('entries')
            ->join('books', 'books.id', '=', 'entries.book_id')
            ->whereIn('books.business_id', $businessIds)
            ->where('entries.date', '>=', $since->toDateString())
            ->groupBy('books.business_id', 'entries.date')
            ->selectRaw('books.business_id AS business_id, entries.date AS d')
            ->selectRaw("SUM(CASE WHEN entries.type = 'in' THEN entries.amount ELSE -entries.amount END) AS net")
            ->get();

        $out = array_fill_keys($businessIds, $empty);
        $months = array_fill_keys($businessIds, ['this' => 0.0, 'last' => 0.0, 'lastSeen' => false]);

        foreach ($rows as $row) {
            $id = (string) $row->business_id;
            if (! isset($out[$id])) {
                continue;
            }

            $date = \Carbon\Carbon::parse($row->d)->startOfDay();
            $net  = (float) $row->net;

            $index = (int) $trendStart->diffInDays($date, absolute: false);
            if ($index >= 0 && $index < self::TREND_DAYS) {
                $out[$id]['trend'][$index] += $net;
            }

            if ($date->gte($thisMonthStart)) {
                $months[$id]['this'] += $net;
            } elseif ($date->gte($lastMonthStart)) {
                $months[$id]['last']     += $net;
                $months[$id]['lastSeen']  = true;
            }
        }

        foreach ($months as $id => $m) {
            // No activity at all last month → nothing honest to compare against.
            if (! $m['lastSeen'] || abs($m['last']) < 0.005) {
                continue;
            }
            $out[$id]['monthChangePct'] = round((($m['this'] - $m['last']) / abs($m['last'])) * 100, 1);
        }

        return $out;
    }

    /**
     * The book a new book should carry its opening balance forward from:
     * the business's book with the latest period_ends_at, falling back to the
     * latest created_at when periods aren't set. Optionally excludes a book
     * (used when offering a carry-forward while editing an existing one).
     *
     * Portable NULLS LAST — Postgres sorts NULLs first on DESC, SQLite last.
     */
    public function previousBookForCarryForward(?string $excludeBookId = null): ?Book
    {
        return $this->books()
            ->when($excludeBookId, fn ($q) => $q->whereKeyNot($excludeBookId))
            ->orderByRaw('CASE WHEN period_ends_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('period_ends_at')
            ->orderByDesc('created_at')
            ->first();
    }

    public function userRole(User $user): ?string
    {
        $member = $this->members()->where('users.id', $user->id)->first();
        return $member?->pivot->role;
    }
}
