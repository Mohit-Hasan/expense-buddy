<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class UserSessionManager
{
    public function __construct(
        private readonly ActiveSessionService $activeSessionService,
    ) {
    }

    public function stampVersionOnLogin(User $user, Session $session): void
    {
        $session->put('auth_session_version', $user->session_version);
    }

    public function logoutOtherDevices(User $user, Request $request): int
    {
        $currentSessionId = $request->session()->getId();

        $deleted = $this->activeSessionService->destroyAllExcept($user, $currentSessionId);

        $user->increment('session_version');
        $user->refresh();

        $this->stampVersionOnLogin($user, $request->session());
        $this->rotateRememberTokenForCurrentDevice($user, $request);

        return $deleted;
    }

    public function logoutDevice(User $user, Request $request, string $sessionId): bool
    {
        if (! $this->activeSessionService->destroyForUser($user, $sessionId, $request->session()->getId())) {
            return false;
        }

        $this->rotateRememberTokenForCurrentDevice($user, $request);

        return true;
    }

    private function rotateRememberTokenForCurrentDevice(User $user, Request $request): void
    {
        $guard = Auth::guard('web');
        $recallerName = $guard->getRecallerName();
        $remembered = $request->cookies->has($recallerName);

        $user->setRememberToken(Str::random(60));
        $user->save();

        if ($remembered) {
            $guard->login($user, true);
        }
    }
}
