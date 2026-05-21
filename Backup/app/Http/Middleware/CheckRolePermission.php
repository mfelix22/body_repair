<?php

namespace App\Http\Middleware;

use App\Helpers\PermissionHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRolePermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $resource, string $action = 'view'): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $allowed = match ($action) {
            'create', 'store' => PermissionHelper::canCreate($resource),
            'update', 'edit' => PermissionHelper::canUpdate($resource),
            'delete', 'destroy' => PermissionHelper::canDelete($resource),
            default => PermissionHelper::canView($resource),
        };

        if (!$allowed) {
            return PermissionHelper::denyAccess($resource, $action);
        }

        return $next($request);
    }
}
