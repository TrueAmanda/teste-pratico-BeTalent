<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        switch ($role) {
            case 'admin':
                if (!$user->isAdmin()) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
                break;
            case 'manager':
                if (!$user->isManager()) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
                break;
            case 'finance':
                if (!$user->isFinance()) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
                break;
            case 'user':
                // All authenticated users can access user routes
                break;
            default:
                return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
