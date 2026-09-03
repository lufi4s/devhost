<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorize that the authenticated user holds one of the given role slugs.
 *
 * The app stores roles as a direct users.role_id -> roles.slug relationship
 * (not Spatie's model_has_roles pivot), so the middleware reads the slug off
 * the role relation rather than relying on Spatie assignments.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $slug = $user->role?->slug;

        if (! $slug || ! in_array($slug, $roles, true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
