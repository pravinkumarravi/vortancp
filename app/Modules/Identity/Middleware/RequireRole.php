<?php

declare(strict_types=1);

namespace App\Modules\Identity\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('login'));
        }

        // Super Admin bypasses all role constraints
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->hasRole($roles)) {
            abort(403, 'Unauthorized access: You do not possess the required role permissions for this interface.');
        }

        return $next($request);
    }
}
