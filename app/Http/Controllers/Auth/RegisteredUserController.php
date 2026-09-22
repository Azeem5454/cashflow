<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StarterWorkspace;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        return view('auth.register', [
            'invitationRedirect' => self::safeInvitationPath($request->query('redirect')),
        ]);
    }

    /**
     * The invitation accept page links here with ?redirect=<accept URL>.
     * Only a same-site /invitations/{token}/accept path is honoured, so this
     * can never become an open redirect.
     */
    public static function safeInvitationPath(mixed $url): ?string
    {
        if (! is_string($url) || $url === '' || strlen($url) > 512) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;
        if ($host !== null && strcasecmp($host, parse_url(config('app.url'), PHP_URL_HOST) ?? '') !== 0
            && strcasecmp($host, request()->getHost()) !== 0) {
            return null;
        }

        $path = $parts['path'] ?? '';

        return preg_match('#^/invitations/[A-Za-z0-9]{16,128}/accept$#', $path) === 1 ? $path : null;
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, StarterWorkspace $starter): RedirectResponse
    {
        // Cloudflare Turnstile anti-bot verification. Skipped when the keys
        // aren't configured so local dev / CI keeps working.
        $this->verifyTurnstile($request);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            // No confirm field — the form has a show/hide toggle instead.
            'password' => ['required', Rules\Password::defaults()],
            'intended_plan' => ['nullable', 'in:pro'],
        ]);

        $invitationPath = self::safeInvitationPath($request->input('redirect'));

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // First-run: a starter business + this month's book, unless they're
        // signing up to join someone else's business via an invitation.
        if (! $invitationPath) {
            $starter->provision($user, StarterWorkspace::DEFAULT_CURRENCY);
        }

        Auth::login($user);

        if ($invitationPath) {
            return redirect($invitationPath);
        }

        // If the user signed up from the Pro tier on the landing page, take
        // them straight to billing to complete Stripe Checkout.
        if ($request->input('intended_plan') === 'pro') {
            return redirect(route('billing', ['auto' => 1]));
        }

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * Call Cloudflare's siteverify endpoint. Throws a ValidationException if
     * the widget response is missing or rejected. No-op if TURNSTILE_SECRET_KEY
     * isn't set (local dev).
     */
    private function verifyTurnstile(Request $request): void
    {
        $secret = config('services.turnstile.secret_key');
        if (empty($secret)) {
            return; // Feature disabled — skip verification.
        }

        $token = $request->input('cf-turnstile-response');
        if (empty($token)) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Please complete the security check before continuing.',
            ]);
        }

        try {
            $response = Http::asForm()->timeout(5)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]
            );

            $body = $response->json();
            if (! ($body['success'] ?? false)) {
                throw ValidationException::withMessages([
                    'cf-turnstile-response' => 'Security check failed. Please refresh and try again.',
                ]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Network hiccup talking to Cloudflare — fail closed so bots can't
            // just DOS the siteverify endpoint to bypass the check.
            Log::warning('Turnstile verify network error', ['message' => $e->getMessage()]);
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Could not verify the security check right now. Please try again in a moment.',
            ]);
        }
    }
}
