<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Support\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $settings = SystemSetting::query()->first();
        $name = Brand::appName($settings);
        $shortName = mb_strlen($name) > 12 ? mb_substr($name, 0, 12) : $name;
        $logoUrl = $this->logoUrl($settings);

        $icons = $logoUrl !== null
            ? $this->logoIcons($logoUrl)
            : $this->defaultIcons();

        return response()->json([
            'name' => $name,
            'short_name' => $shortName,
            'description' => Brand::tagline(),
            'start_url' => url('/login'),
            'scope' => url('/'),
            'display' => 'standalone',
            'background_color' => '#f8fafc',
            'theme_color' => '#0d9488',
            'orientation' => 'any',
            'icons' => $icons,
        ], Response::HTTP_OK, ['Content-Type' => 'application/manifest+json']);
    }

    public function favicon(): RedirectResponse
    {
        $settings = SystemSetting::query()->first();
        $logoUrl = $this->logoUrl($settings);

        if ($logoUrl !== null) {
            return redirect($logoUrl);
        }

        return redirect(asset('favicon.svg'));
    }

    private function logoUrl(?SystemSetting $settings): ?string
    {
        if ($settings?->system_logo && Storage::disk('public')->exists($settings->system_logo)) {
            return asset('storage/'.$settings->system_logo);
        }

        return null;
    }

    /**
     * @return list<array{src: string, sizes: string, type: string, purpose: string}>
     */
    private function logoIcons(string $logoUrl): array
    {
        return [
            [
                'src' => $logoUrl,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $logoUrl,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $logoUrl,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ];
    }

    /**
     * @return list<array{src: string, sizes: string, type: string, purpose: string}>
     */
    private function defaultIcons(): array
    {
        return [
            [
                'src' => asset('icons/icon-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => asset('icons/icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
        ];
    }
}
