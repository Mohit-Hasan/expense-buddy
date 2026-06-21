<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InstallRequest;
use App\Services\InstallService;
use App\Support\AppInstall;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InstallController extends Controller
{
    public function __construct(
        private readonly InstallService $installService,
    ) {}

    public function show(): View|RedirectResponse
    {
        if (AppInstall::isInstalled()) {
            return redirect()->route('login');
        }

        return view('install.index', [
            'requirements' => AppInstall::requirements(),
            'requirementsMet' => AppInstall::requirementsMet(),
            'defaultSystemName' => Brand::name(),
            'defaultTagline' => Brand::tagline(),
        ]);
    }

    public function store(InstallRequest $request): RedirectResponse
    {
        if (! AppInstall::requirementsMet()) {
            return redirect()
                ->route('install.show')
                ->withErrors(['install' => 'Complete the server requirements before installing.']);
        }

        $this->installService->install(
            $request->safe()->except(['system_logo', 'admin_password_confirmation']),
            $request->file('system_logo'),
        );

        return redirect()
            ->route('login')
            ->with('success', 'ExpenseBuddy is ready. Sign in with your administrator account.');
    }
}
