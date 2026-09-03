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

        // Laravel may or may not split the middleware parameters on '|'; normalize
        // so "super_admin|admin" resolves to ['super_admin', 'admin'] either way.
        $allowed = [];
        foreach ($roles as $role) {
            foreach (explode('|', $role) as $single) {
                if ($single !== '') {
                    $allowed[] = $single;
                }
            }
        }

        if (! $slug || ! in_array($slug, $allowed, true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
