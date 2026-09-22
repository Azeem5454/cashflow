<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Soft email verification for Livewire actions that email other people
 * (team invitations, email reports). Unverified users get an inline notice
 * with a Resend button (<x-verify-email-notice />) instead of a hard block
 * on the whole app.
 */
trait RequiresVerifiedEmail
{
    public bool $emailVerificationRequired = false;
    public bool $verificationEmailResent   = false;

    /**
     * Re-reads the user so verifying in another tab takes effect without a
     * page reload. Returns false (and flags the notice) when unverified.
     */
    protected function ensureVerifiedEmail(): bool
    {
        $user = auth()->user()?->fresh();

        if ($user && ! $user->hasVerifiedEmail()) {
            $this->emailVerificationRequired = true;

            return false;
        }

        $this->emailVerificationRequired = false;

        return true;
    }

    public function resendVerificationEmail(): void
    {
        $user = auth()->user()?->fresh();

        if (! $user || $user->hasVerifiedEmail()) {
            $this->emailVerificationRequired = false;

            return;
        }

        // 3 resends per 10 minutes per user; extra clicks just show "sent".
        $key = 'verify-resend:' . $user->id;
        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 600);
            $user->sendEmailVerificationNotification();
        }

        $this->verificationEmailResent = true;
    }
}
