<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\MenuPermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $permission = MenuPermissionRegistry::permissionForRoute($request->route()?->getName());

        if ($permission === null) {
            return $next($request);
        }

        if (! $request->user()?->hasPermission($permission)) {
            abort(403, 'You do not have permission to access this menu.');
        }

        return $next($request);
    }
}
