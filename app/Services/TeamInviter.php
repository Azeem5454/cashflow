<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Invitation;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for team invitations (web settings + mobile API).
 *
 * - Emails are compared case-insensitively ("Sara@X.com" === "sara@x.com").
 * - Existing members (including the owner) can't be invited.
 * - Re-inviting an email that already has an invitation for this business
 *   refreshes that invitation (new token, role, 72h expiry) instead of
 *   creating a duplicate.
 */
class TeamInviter
{
    public static function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function isMember(Business $business, string $email): bool
    {
        return $business->members()
            ->whereRaw('LOWER(users.email) = ?', [self::normalize($email)])
            ->exists();
    }

    /**
     * @return array{0: Invitation, 1: bool} the invitation and whether an existing one was refreshed
     *
     * @throws ValidationException when the email already belongs to a member
     */
    public function invite(Business $business, string $email, string $role, string $errorKey = 'email'): array
    {
        $email = self::normalize($email);

        if ($this->isMember($business, $email)) {
            throw ValidationException::withMessages([
                $errorKey => 'This person is already a team member.',
            ]);
        }

        $existing = $business->invitations()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->oldest()
            ->get();

        if ($existing->isNotEmpty()) {
            $invitation = $existing->shift();
            $invitation->forceFill([
                'email'       => $email,
                'role'        => $role,
                'token'       => Str::random(64),
                'accepted_at' => null,
                'expires_at'  => now()->addHours(72),
            ])->save();

            // Clean up duplicates created before this fix.
            $existing->each->delete();

            return [$invitation, true];
        }

        $invitation = $business->invitations()->create([
            'email' => $email,
            'role'  => $role,
        ]);

        return [$invitation, false];
    }
}
