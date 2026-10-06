<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceChangePassword
{
    /**
     * Redirect user to their profile page if they must change their password.
     * Exempts profile/password routes and logout to avoid redirect loops.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // Allow profile and logout routes to pass through
            $allowedRoutes = ['profile.show', 'profile.update', 'profile.password', 'logout'];
            if (! in_array($request->route()?->getName(), $allowedRoutes, true)) {
                return redirect()->route('profile.show')
                    ->with('error', 'You must change your password before continuing.');
            }
        }

        return $next($request);
    }
}
