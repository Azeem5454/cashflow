<?php

namespace App\Services;

use App\Models\User;

/**
 * Find-or-create logic for social sign-in, shared by the web Google flow,
 * the mobile Google flow and native Sign in with Apple so they all behave
 * identically.
 *
 * Rules:
 *   - Existing users keep their password + plan untouched. We only stamp
 *     provider/provider_id the first time (when neither is set).
 *   - New users get plan=free, email_verified_at=now() (the provider verified
 *     the email) and a random password they never see.
 *   - `plan`, `provider`, `provider_id` are NOT mass-assignable — always set
 *     via explicit property assignment.
 */
class SocialAccountService
{
    /**
     * Google (web + mobile): match on email — Google emails are verified.
     */
    public function resolveGoogleUser(string $providerId, string $email, ?string $name): User
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->stampProviderIfMissing($user, 'google', $providerId);

            return $user;
        }

        return $this->createUser('google', $providerId, $email, $name);
    }

    /**
     * Apple: match on the stable `sub` first (the email can be a private relay
     * address or change later), then on the token's verified email.
     * Returns null when there's no sub match and no verified email to go on.
     */
    public function resolveAppleUser(string $sub, ?string $verifiedEmail, ?string $name): ?User
    {
        $user = User::where('provider', 'apple')->where('provider_id', $sub)->first();
        if ($user) {
            return $user;
        }

        if (! $verifiedEmail) {
            return null;
        }

        $user = User::where('email', $verifiedEmail)->first();
        if ($user) {
            $this->stampProviderIfMissing($user, 'apple', $sub);

            return $user;
        }

        return $this->createUser('apple', $sub, $verifiedEmail, $name);
    }

    private function stampProviderIfMissing(User $user, string $provider, string $providerId): void
    {
        if (! $user->provider_id && ! $user->provider) {
            $user->provider    = $provider;
            $user->provider_id = $providerId;
            $user->save();
        }
    }

    private function createUser(string $provider, string $providerId, string $email, ?string $name): User
    {
        $name = trim((string) $name);
        if ($name === '') {
            $name = trim(explode('@', $email)[0]) ?: 'New user';
        }

        $user = new User();
        $user->name              = mb_substr($name, 0, 255);
        $user->email             = $email;
        $user->password          = bcrypt(bin2hex(random_bytes(16))); // unusable until they reset
        $user->email_verified_at = now();
        // plan + provider are NOT fillable; set explicitly.
        $user->plan        = 'free';
        $user->provider    = $provider;
        $user->provider_id = $providerId;
        // The random password above is unknown to the user — they can set a
        // real one via the reset-link flow, which flips this back to true.
        $user->has_password = false;
        $user->save();

        return $user;
    }
}
