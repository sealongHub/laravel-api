<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureRole
{
    /**
     * Handle an incoming request.
     * Usage in routes: ->middleware(\App\Http\Middleware\EnsureRole::class . ':product_staff,manager,super_admin')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Require a bearer token to check roles
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['errors' => ['message' => ['Unauthenticated']]], 401);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['errors' => ['message' => ['Unauthenticated']]], 401);
        }

        $user->load('profiles');
        $type = $user->profiles?->type ?? null;

        if (!$type) {
            return response()->json(['errors' => ['message' => ['Forbidden: no role']]], 403);
        }

        if (!in_array($type, $roles)) {
            return response()->json(['errors' => ['message' => ['Forbidden: insufficient role']]], 403);
        }

        return $next($request);
    }
}
