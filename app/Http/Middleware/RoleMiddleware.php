<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: `->middleware('role:admin')` or `role:l1,l2`.
 *
 * This is a coarse gate for whole route groups. Fine-grained rules
 * (who may edit which ticket) live in TicketPolicy / UserPolicy.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! in_array($user->roleName()?->value, $roles, true)) {
            abort(403, 'Your role does not have access to this section.');
        }

        return $next($request);
    }
}
