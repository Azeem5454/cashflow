<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow admins who are impersonating a user to access app routes
        if (session()->has('impersonating_admin_id')) {
            return $next($request);
        }

        if (auth()->check() && auth()->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
