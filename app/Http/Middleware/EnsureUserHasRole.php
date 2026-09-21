<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Eager load role if not already loaded
        if (! $user->relationLoaded('role')) {
            $user->load('role');
        }

        $userRoleSlug = $user->role?->slug;

        // Super Admin has bypass access to all administrative routes
        if ($userRoleSlug === 'super_admin') {
            return $next($request);
        }

        if (! in_array($userRoleSlug, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden: You do not have permission to access this resource.'], 403);
            }
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
