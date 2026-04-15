<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Facades\Socialite;

/**
 * Social (OAuth) sign-in — currently Google only.
 *
 * Flow:
 *   1. User clicks "Continue with Google" on /login or /register.
 *   2. They're redirected to Google's consent screen.
 *   3. Google bounces them back to /auth/google/callback with a code.
 *   4. We exchange the code for user info (email, name, verified flag).
 *   5. If a local user exists for that email, we log them in and (first time
 *      only) stamp provider + provider_id on the row.
 *      Otherwise we create a new user with plan=free, email_verified_at=now()
 *      (Google verifies emails), and a secure random password they'll never
 *      use (they can set one later via password reset).
 *
 * Security notes:
 *   - We match on *email*, not provider_id, for the link decision — otherwise
 *     a user who signed up with email first and later tries Google would get
 *     a duplicate account. Google emails are always verified.
 *   - The callback is rate-limited (10/min per IP) to slow down abuse.
 *   - Provider/provider_id are NOT in User::$fillable; set explicitly.
 *   - We never store the access token — we only need the subject ID.
 */
class SocialAuthController extends Controller
{
    private const SUPPORTED_PROVIDERS = ['google'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED_PROVIDERS, true), 404);

        if (empty(config("services.{$provider}.client_id"))) {
            return redirect()->route('login')->withErrors([
                'social' => 'Social sign-in is temporarily unavailable. Please sign in with your email.',
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED_PROVIDERS, true), 404);

        $rateKey = 'social-callback:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            return redirect()->route('login')->withErrors([
                'social' => 'Too many sign-in attempts. Please wait a minute and try again.',
            ]);
        }
        RateLimiter::hit($rateKey, 60);

        // Handle user-cancel / provider error gracefully.
        if ($request->has('error')) {
            return redirect()->route('login')->with('status', 'Sign-in was cancelled.');
        }

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::warning('Social OAuth callback failed', [
                'provider' => $provider,
                'ip'       => $request->ip(),
                'message'  => $e->getMessage(),
            ]);
            return redirect()->route('login')->withErrors([
                'social' => 'We couldn\'t complete sign-in with ' . ucfirst($provider) . '. Please try again or use email.',
            ]);
        }

        $email = $social->getEmail();
        if (! $email) {
            return redirect()->route('login')->withErrors([
                'social' => ucfirst($provider) . ' didn\'t share an email address with us. Please sign up with your email.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            // Existing user — log in, stamp provider columns the first time.
            if (! $user->provider_id && ! $user->provider) {
                $user->provider    = $provider;
                $user->provider_id = $social->getId();
                $user->save();
            }
        } else {
            // New user — create with verified email.
            $user = new User();
            $user->name              = $social->getName() ?: trim(explode('@', $email)[0]);
            $user->email             = $email;
            $user->password          = bcrypt(bin2hex(random_bytes(16))); // unusable until they reset
            $user->email_verified_at = now();
            $user->save();

            // plan + provider are NOT fillable; set explicitly.
            $user->plan        = 'free';
            $user->provider    = $provider;
            $user->provider_id = $social->getId();
            $user->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
