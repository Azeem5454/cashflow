<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\AppleIdentityTokenVerifier;
use App\Services\AppleTokenInvalid;
use App\Services\MobileSocialLogin;
use App\Services\SocialAccountService;
use App\Services\StarterWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Mobile social sign-in. Both endpoints are public (no auth:sanctum, no
 * api.verified) and return exactly the same shape as POST /auth/login:
 * { "user": UserResource, "token": "<sanctum plain-text token>" }.
 *
 * Admins are refused (403) — they use the web.
 */
class SocialAuthController extends Controller
{
    /**
     * POST /api/v1/auth/social/exchange  { code, codeVerifier? }
     * Redeems the one-time code issued by the Google mobile callback.
     */
    public function exchange(Request $request, MobileSocialLogin $mobileLogin, StarterWorkspace $starter): JsonResponse
    {
        $validated = $request->validate([
            'code'         => ['required', 'string', 'min:32', 'max:256'],
            'codeVerifier' => ['sometimes', 'nullable', 'string', 'min:43', 'max:128'],
            // Optional device currency for a brand-new account's first business.
            // Lenient: anything that isn't a 3-letter code falls back to USD.
            'currency'     => ['sometimes', 'nullable', 'string', 'max:8'],
        ]);

        $payload = $mobileLogin->consumeCodePayload($validated['code'], $validated['codeVerifier'] ?? null);
        $user    = $payload ? User::find($payload['user_id']) : null;

        if (! $user) {
            Log::info('Mobile social code exchange rejected', ['ip' => $request->ip()]);

            return response()->json([
                'message' => 'This sign-in link is invalid or has expired. Please try again.',
            ], 422);
        }

        // See AuthController::register — the first business is created by the
        // user, with a currency they chose.
        $onboarding = null;

        return $this->issueToken($user, $onboarding);
    }

    /**
     * POST /api/v1/auth/apple  { identityToken, fullName?: {givenName, familyName}, email? }
     *
     * `email` in the body is ignored for matching — only the verified email
     * inside Apple's signed token is trusted.
     */
    public function apple(
        Request $request,
        AppleIdentityTokenVerifier $verifier,
        SocialAccountService $accounts,
        StarterWorkspace $starter,
    ): JsonResponse {
        $validated = $request->validate([
            'identityToken'       => ['required', 'string', 'max:8192'],
            'fullName'            => ['sometimes', 'nullable', 'array'],
            'fullName.givenName'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'fullName.familyName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'currency'            => ['sometimes', 'nullable', 'string', 'max:8'],
        ]);

        try {
            $claims = $verifier->verify($validated['identityToken']);
        } catch (AppleTokenInvalid $e) {
            Log::warning('Apple identity token rejected', ['reason' => $e->getMessage(), 'ip' => $request->ip()]);

            return response()->json(['message' => 'Apple sign-in could not be verified. Please try again.'], 401);
        } catch (\Throwable $e) {
            Log::error('Apple sign-in unavailable', ['error' => class_basename($e), 'message' => $e->getMessage()]);

            return response()->json(['message' => 'Apple sign-in is temporarily unavailable. Please try again shortly.'], 503);
        }

        $email = null;
        if (is_string($claims->email ?? null)
            && in_array($claims->email_verified ?? null, [true, 'true'], true)
            && filter_var($claims->email, FILTER_VALIDATE_EMAIL)) {
            $email = $claims->email;
        }

        $name = trim(strip_tags(implode(' ', array_filter([
            $validated['fullName']['givenName'] ?? null,
            $validated['fullName']['familyName'] ?? null,
        ]))));

        $user = $accounts->resolveAppleUser($claims->sub, $email, $name !== '' ? $name : null);

        if (! $user) {
            return response()->json([
                'message' => 'Apple didn\'t share a verified email address. Please sign up with your email.',
            ], 422);
        }

        $onboarding = null;

        return $this->issueToken($user, $onboarding);
    }

    /**
     * @param  array{businessId: string, bookId: string}|null  $onboarding  set when a starter workspace was just created
     */
    private function issueToken(User $user, ?array $onboarding = null): JsonResponse
    {
        if ($user->is_admin) {
            return response()->json([
                'message' => 'Admin accounts must sign in on the web.',
            ], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json(array_filter([
            'user'       => new UserResource($user),
            'token'      => $token,
            'onboarding' => $onboarding,
        ], fn ($v) => $v !== null));
    }
}
