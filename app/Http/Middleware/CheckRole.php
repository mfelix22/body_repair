<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $roles = null): Response
    {
        if ($roles === null) {
            return $next($request);
        }

        $allowedRoles = array_values(array_filter(array_map('trim', explode('|', $roles))));
        $user = auth()->user();

        if ($user && $user->hasAnyRole($allowedRoles)) {
            return $next($request);
        }

        if (!$user) {
            return redirect()->route('login');
        }

        return redirect()->route('dashboard')
            ->with('error', "You don't have permission to access this page. Please contact your administrator if you need access.");
    }
}
