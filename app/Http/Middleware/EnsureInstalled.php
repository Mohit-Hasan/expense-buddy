<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AppInstall;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('up', 'install', 'install/*')) {
            return $next($request);
        }

        $isPublicAssetRoute = $request->routeIs('pwa.*')
            || $request->is('build/*', 'storage/*', 'icons/*', 'sw.js', 'favicon.svg');

        if ($isPublicAssetRoute) {
            return $next($request);
        }

        if (! AppInstall::isInstalled()) {
            return redirect('/install/');
        }

        return $next($request);
    }
}
