<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        $isApi = $request->expectsJson() || $request->is('api/*');

        if (!$user) {
            return $isApi
                ? response()->json(['success' => false, 'message' => 'Autentikasi dibutuhkan. Silakan login terlebih dahulu.'], 401)
                : redirect()->route('login');
        }

        if (!empty($roles) && !in_array($user->role, $roles, true)) {
            return $isApi
                ? response()->json(['success' => false, 'message' => 'Akses ditolak. Fitur ini hanya untuk peran: ' . implode(', ', $roles)], 403)
                : abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
