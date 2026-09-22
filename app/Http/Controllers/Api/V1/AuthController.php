<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // plan is not in $fillable (mass-assignment protection). Set explicitly.
        $user->plan = 'free';
        $user->save();

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        // Admins cannot login via API
        if ($user->is_admin) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Name prefix of the per-device, biometric-gated sign-in tokens.
     * The full name is `mobile-biometric:{deviceId}`.
     */
    public const BIOMETRIC_TOKEN_PREFIX = 'mobile-biometric:';

    /**
     * POST /api/v1/auth/biometric-token — mint a long-lived token the app keeps
     * in a Face ID / fingerprint-gated keychain item, so the user can sign back
     * in with biometrics after signing out. One token per user per device:
     * any previous token with the same device name is replaced.
     *
     * Body: { deviceId?: string (≤100) } → 201 { token }
     */
    public function createBiometricToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deviceId' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._\-]+$/'],
        ]);

        $user = $request->user();
        if ($user->is_admin) {
            return response()->json(['message' => 'Not available for this account.'], 403);
        }

        $name = self::BIOMETRIC_TOKEN_PREFIX . ($validated['deviceId'] ?? 'default');

        $user->tokens()->where('name', $name)->delete();
        $token = $user->createToken($name)->plainTextToken;

        return response()->json(['token' => $token], 201);
    }

    /**
     * DELETE /api/v1/auth/biometric-token — revoke this device's biometric token.
     *
     * Body: { deviceId?: string (≤100) } → 200 { message }
     */
    public function deleteBiometricToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deviceId' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._\-]+$/'],
        ]);

        $name = self::BIOMETRIC_TOKEN_PREFIX . ($validated['deviceId'] ?? 'default');
        $request->user()->tokens()->where('name', $name)->delete();

        return response()->json(['message' => 'Biometric sign-in removed.']);
    }

    /**
     * POST /api/v1/auth/biometric-login — called WITH a biometric token as the
     * bearer. Returns a fresh normal session token (same shape as auth/login),
     * so signing out later revokes only that session and the biometric token
     * keeps working. Any other token type gets 403.
     */
    public function biometricLogin(Request $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentAccessToken();
        $name = is_object($current) && isset($current->name) ? (string) $current->name : '';

        if (! str_starts_with($name, self::BIOMETRIC_TOKEN_PREFIX) || $user->is_admin) {
            return response()->json(['message' => 'Biometric sign-in token required.'], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * GET /api/v1/user
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'message' => 'If an account exists with that email, a reset link has been sent.',
        ]);
    }

    /**
     * PUT /api/v1/profile — update name / email
     */
    public function updateProfile(Request $request): UserResource|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        // Social-linked accounts: the provider (Google / Apple) owns the email.
        // Name changes are fine; an actual email change is refused.
        if (isset($validated['email']) && $user->authProvider()) {
            if (mb_strtolower($validated['email']) !== mb_strtolower($user->email)) {
                return response()->json([
                    'message' => 'Your email is managed by your ' . $user->providerLabel() . ' account.',
                    'errors'  => ['email' => ['Your email is managed by your ' . $user->providerLabel() . ' account.']],
                ], 422);
            }
            unset($validated['email']);
        }

        $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;

        $user->update($validated);

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
            $user->sendEmailVerificationNotification();
        }

        return new UserResource($user->fresh());
    }

    /**
     * PUT /api/v1/profile/password — change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Social-created accounts have no password they know. They set one via
        // the emailed reset link (POST /auth/forgot-password), which proves
        // mailbox ownership — never by setting one directly with a bearer token.
        if (! $user->has_password) {
            return response()->json([
                'message' => "Use 'Set a password' — we'll email you a secure link.",
            ], 422);
        }

        $request->validate([
            'currentPassword' => ['required', 'string'],
            'password'        => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($request->input('currentPassword'), $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->password     = Hash::make($request->input('password'));
        $user->has_password = true;
        $user->save();

        // Revoke all OTHER tokens (keep current session alive)
        $currentTokenId = $request->user()->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json(['message' => 'Password updated.']);
    }

    /**
     * POST /api/v1/auth/email/resend — resend verification email
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }

    /**
     * DELETE /api/v1/profile — delete account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->has_password) {
            $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (! Hash::check($request->input('password'), $user->password)) {
                return response()->json(['message' => 'Password is incorrect.'], 422);
            }
        } else {
            // Social-created account with no known password: confirm by typing
            // the account email instead (same as the web Danger Zone).
            $request->validate([
                'confirmEmail' => ['required', 'string', 'max:255'],
            ]);

            if (mb_strtolower(trim($request->input('confirmEmail'))) !== mb_strtolower($user->email)) {
                return response()->json(['message' => 'Email address does not match.'], 422);
            }
        }

        // Stop billing before the account disappears — otherwise Stripe keeps
        // charging a customer we no longer have a record of.
        if ($user->subscribed('default')) {
            try {
                $user->subscription('default')->cancelNow();
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'We could not cancel your subscription. Please try again or contact support.',
                ], 422);
            }
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted.']);
    }
}
