<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MobileSocialLogin;
use App\Services\SocialAccountService;
use App\Services\StarterWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
 *
 * Mobile: GET /auth/google/mobile starts the same Google flow but, on the
 * callback, redirects to the app's deep link with a one-time code (see
 * App\Services\MobileSocialLogin) instead of logging the browser in.
 */
class SocialAuthController extends Controller
{
    private const SUPPORTED_PROVIDERS = ['google'];

    public function __construct(
        private readonly SocialAccountService $accounts,
        private readonly MobileSocialLogin $mobileLogin,
    ) {
    }

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED_PROVIDERS, true), 404);

        // A plain web sign-in must never inherit a stale, abandoned mobile flow.
        $request->session()->forget(MobileSocialLogin::SESSION_KEY);

        if (empty(config("services.{$provider}.client_id"))) {
            return redirect()->route('login')->withErrors([
                'social' => 'Social sign-in is temporarily unavailable. Please sign in with your email.',
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * GET /auth/google/mobile?redirect_uri=thecashfox://...[&code_challenge=...]
     *
     * Entry point for the mobile app (opened in an in-app browser). Guest-
     * agnostic: a logged-in browser session doesn't matter here — we never
     * log the browser in on the mobile path.
     */
    public function mobileRedirect(Request $request): RedirectResponse|Response
    {
        $redirectUri = $request->query('redirect_uri');

        if (! MobileSocialLogin::isAllowedRedirectUri($redirectUri)) {
            // Never redirect to an unvalidated URI.
            return response('Invalid redirect_uri.', 400);
        }

        $challenge = $request->query('code_challenge');
        if ($challenge !== null) {
            $method = $request->query('code_challenge_method', 'S256');
            if (! MobileSocialLogin::isValidChallenge($challenge) || $method !== 'S256') {
                return redirect()->away(MobileSocialLogin::appendQuery($redirectUri, ['error' => 'failed']));
            }
        }

        if (empty(config('services.google.client_id'))) {
            return redirect()->away(MobileSocialLogin::appendQuery($redirectUri, ['error' => 'failed']));
        }

        $request->session()->put(MobileSocialLogin::SESSION_KEY, [
            'mobile'         => 1,
            'redirect_uri'   => $redirectUri,
            'code_challenge' => $challenge,
        ]);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED_PROVIDERS, true), 404);

        // Mobile flow? Pull (and forget) the flag up-front so every exit path
        // below goes back to the app, and it can never be replayed.
        $mobile = $request->session()->pull(MobileSocialLogin::SESSION_KEY);
        if (! is_array($mobile) || empty($mobile['mobile'])
            || ! MobileSocialLogin::isAllowedRedirectUri($mobile['redirect_uri'] ?? null)) {
            $mobile = null;
        }

        // Web flow keeps the old `guest` middleware behaviour (the route now
        // sits outside the guest group so the mobile path works even when the
        // in-app browser already has a web session).
        if (! $mobile && Auth::check()) {
            return redirect()->route('dashboard');
        }

        $rateKey = 'social-callback:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            return $mobile
                ? $this->mobileError($mobile, 'failed')
                : redirect()->route('login')->withErrors([
                    'social' => 'Too many sign-in attempts. Please wait a minute and try again.',
                ]);
        }
        RateLimiter::hit($rateKey, 60);

        // Handle user-cancel / provider error gracefully.
        if ($request->has('error')) {
            return $mobile
                ? $this->mobileError($mobile, 'cancelled')
                : redirect()->route('login')->with('status', 'Sign-in was cancelled.');
        }

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::warning('Social OAuth callback failed', [
                'provider' => $provider,
                'mobile'   => (bool) $mobile,
                'ip'       => $request->ip(),
                'message'  => $e->getMessage(),
            ]);

            return $mobile
                ? $this->mobileError($mobile, 'failed')
                : redirect()->route('login')->withErrors([
                    'social' => 'We couldn\'t complete sign-in with ' . ucfirst($provider) . '. Please try again or use email.',
                ]);
        }

        $email = $social->getEmail();
        if (! $email) {
            return $mobile
                ? $this->mobileError($mobile, 'failed')
                : redirect()->route('login')->withErrors([
                    'social' => ucfirst($provider) . ' didn\'t share an email address with us. Please sign up with your email.',
                ]);
        }

        $user = $this->accounts->resolveGoogleUser((string) $social->getId(), $email, $social->getName());

        if ($mobile) {
            // Don't log the browser in — hand the app a one-time code instead.
            // A brand-new account's starter workspace is created on exchange,
            // where the app can pass its device currency.
            $code = $this->mobileLogin->issueCode($user->id, $mobile['code_challenge'] ?? null, $user->wasRecentlyCreated);

            return redirect()->away(MobileSocialLogin::appendQuery($mobile['redirect_uri'], ['code' => $code]));
        }

        if ($user->wasRecentlyCreated) {
            app(StarterWorkspace::class)->provision($user, StarterWorkspace::DEFAULT_CURRENCY);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function mobileError(array $mobile, string $error): RedirectResponse
    {
        return redirect()->away(MobileSocialLogin::appendQuery($mobile['redirect_uri'], ['error' => $error]));
    }
}
