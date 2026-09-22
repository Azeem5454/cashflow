<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API equivalent of the web `verified` middleware: data endpoints require a
 * verified email. Returns a JSON 403 the mobile app can recognise by `code`.
 */
class EnsureApiEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email address.',
                'code'    => 'email_unverified',
            ], 403);
        }

        return $next($request);
    }
}
