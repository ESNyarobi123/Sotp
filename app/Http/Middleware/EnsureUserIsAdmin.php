<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Restrict routes to users with the "admin" role (Spatie).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasRole('admin')) {
            return redirect()
                ->route('dashboard')
                ->with('error', __('That area is only available to administrators.'));
        }

        return $next($request);
    }
}
