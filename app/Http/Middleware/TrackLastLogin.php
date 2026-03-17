<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->session()->has('last_login_tracked')) {
            $request->user()->update(['last_login_at' => now()]);
            $request->session()->put('last_login_tracked', true);
        }

        return $next($request);
    }
}
