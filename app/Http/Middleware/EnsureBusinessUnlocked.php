<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Support\BusinessLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Free plan: extra businesses a user owns are locked (same rule as the
 * businesses.show gate and the API). Redirects to billing, like that gate.
 */
class EnsureBusinessUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = $request->route('business');
        $user     = $request->user();

        if ($business instanceof Business && $user) {
            $role = $user->businesses()->where('businesses.id', $business->id)->first()?->pivot?->role;

            if (BusinessLock::isLocked($user, $business, $role)) {
                return redirect()->route('billing');
            }
        }

        return $next($request);
    }
}
