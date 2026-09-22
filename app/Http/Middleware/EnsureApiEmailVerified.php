<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft email verification: applied ONLY to API actions that email other
 * people (team invitations, email report schedules). Everything else works
 * for unverified users. Returns a JSON 403 the app recognises by `code`.
 */
class EnsureApiEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email address first.',
                'code'    => 'email_unverified',
            ], 403);
        }

        return $next($request);
    }
}
