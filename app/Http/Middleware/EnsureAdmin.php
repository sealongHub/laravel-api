<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['errors' => ['message' => ['Unauthenticated']]], 401);
        }

        // load profile relation
        $user->load('profiles');
        $type = $user->profiles?->type ?? null;

        // only super_admin can perform full administrative actions
        if ($type !== 'super_admin') {
            return response()->json(['errors' => ['message' => ['Forbidden: super_admin only']]], 403);
        }

        return $next($request);
    }
}
