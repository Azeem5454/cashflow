<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Cloudflare Turnstile anti-bot verification. Skipped when the keys
        // aren't configured so local dev / CI keeps working.
        $this->verifyTurnstile($request);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'intended_plan' => ['nullable', 'in:pro'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

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
