<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * First-run setup for brand-new accounts: one business ("My Business") and a
 * book for the current month, so a new user can add their first entry right
 * away instead of building the hierarchy by hand.
 *
 * Called ONLY from account-creation paths (web register, API register, social
 * account creation) — existing users are never touched.
 *
 * Rules:
 *   - Idempotent: does nothing when the user already belongs to any business.
 *   - Skipped when the email has a pending team invitation (they're joining
 *     someone else's business, so an empty one of their own is just noise).
 *   - Never throws: failures are logged and signup carries on.
 *   - The auto business IS the Free plan's one business (same as creating it
 *     by hand), so plan limits are unchanged.
 */
class StarterWorkspace
{
    public const DEFAULT_CURRENCY = 'USD';
    public const BUSINESS_NAME    = 'My Business';

    /**
     * Same rule as business creation (web Business\Create + API store):
     * three upper-case letters. Anything else falls back to USD.
     */
    public static function normalizeCurrency(mixed $currency): string
    {
        if (is_string($currency)) {
            $currency = strtoupper(trim($currency));
            if (preg_match('/^[A-Z]{3}$/', $currency) === 1) {
                return $currency;
            }
        }

        return self::DEFAULT_CURRENCY;
    }

    public static function hasPendingInvitation(string $email): bool
    {
        return Invitation::query()
            ->whereRaw('LOWER(email) = ?', [TeamInviter::normalize($email)])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * @return array{businessId: string, bookId: string}|null the created ids, or null when skipped / failed
     */
    public function provision(User $user, mixed $currency = null): ?array
    {
        try {
            if ($user->is_admin || self::hasPendingInvitation($user->email)) {
                return null;
            }

            return DB::transaction(function () use ($user, $currency) {
                // Serialise concurrent calls for the same user (double-tap,
                // retried request) so only one workspace is ever created.
                User::whereKey($user->id)->lockForUpdate()->first();

                if ($user->businesses()->exists()) {
                    return null;
                }

                $business = Business::create([
                    'owner_id' => $user->id,
                    'name'     => self::BUSINESS_NAME,
                    'currency' => self::normalizeCurrency($currency),
                ]);
                $business->members()->attach($user->id, ['role' => 'owner']);

                $month = now()->startOfMonth();
                $book  = $business->books()->create([
                    'name'             => $month->format('F Y'),
                    'opening_balance'  => 0,
                    'period_starts_at' => $month->toDateString(),
                    'period_ends_at'   => $month->copy()->endOfMonth()->toDateString(),
                ]);

                return ['businessId' => $business->id, 'bookId' => $book->id];
            });
        } catch (\Throwable $e) {
            Log::warning('Starter workspace provisioning failed', [
                'user_id' => $user->id,
                'error'   => class_basename($e),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
