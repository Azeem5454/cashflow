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
            'plan'     => 'free',
        ]);

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
    public function updateProfile(Request $request): UserResource
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

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

        $request->validate([
            'currentPassword' => ['required', 'string'],
            'password'        => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($request->input('currentPassword'), $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

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

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Password is incorrect.'], 422);
        }

        // Cancel any active subscription before deleting
        if ($user->subscribed('default')) {
            try {
                $user->subscription('default')->cancelNow();
            } catch (\Throwable) {
                // ignore
            }
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted.']);
    }
}
