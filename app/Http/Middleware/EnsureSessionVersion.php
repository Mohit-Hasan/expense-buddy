<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->hasSession()) {
            return $next($request);
        }

        $user = $request->user();
        $storedVersion = $request->session()->get('auth_session_version');

        if ($storedVersion === null) {
            $request->session()->put('auth_session_version', $user->session_version);

            return $next($request);
        }

        if ((int) $storedVersion !== (int) $user->session_version) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This session was signed out remotely. Please sign in again.',
            ]);
        }

        return $next($request);
    }
}
