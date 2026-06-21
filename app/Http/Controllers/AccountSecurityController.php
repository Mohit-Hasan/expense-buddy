<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSecurityController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $pendingSecret = $request->session()->get('two_factor.setup_secret');
        $qrSvg = null;

        if ($pendingSecret !== null && ! $user->hasTwoFactorEnabled()) {
            $qrSvg = $this->twoFactorService->qrCodeSvg($user, (string) $pendingSecret);
        }

        return view('account.security', [
            'user' => $user,
            'pendingSecret' => $pendingSecret,
            'qrSvg' => $qrSvg,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('success', 'Two-factor authentication is already enabled.');
        }

        $secret = $this->twoFactorService->generateSecret();
        $request->session()->put('two_factor.setup_secret', $secret);

        return back()->with('success', 'Scan the QR code with your authenticator app, then enter the code to confirm.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();
        $secret = $request->session()->get('two_factor.setup_secret');

        if ($secret === null) {
            return back()->withErrors(['code' => 'Start setup again to generate a new QR code.']);
        }

        if (! $this->twoFactorService->verifySetup((string) $secret, $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'The verification code is invalid.']);
        }

        $user->forceFill([
            'two_factor_secret' => $this->twoFactorService->encryptSecret((string) $secret),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor.setup_secret');

        return back()->with('success', 'Two-factor authentication is now enabled.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! \Illuminate\Support\Facades\Hash::check($request->string('password')->toString(), $user->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget('two_factor.setup_secret');

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }
}
