<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Support\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $settings = SystemSetting::query()->first();
        $name = Brand::appName($settings);
        $shortName = mb_strlen($name) > 12 ? mb_substr($name, 0, 12) : $name;
        $customLogo = Brand::customLogoUrl($settings);

        $icons = $customLogo !== null
            ? $this->logoIcons($customLogo)
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
        ], HttpResponse::HTTP_OK, ['Content-Type' => 'application/manifest+json']);
    }

    public function favicon(): Response
    {
        $settings = SystemSetting::query()->first();
        $customLogo = Brand::customLogoUrl($settings);

        if ($customLogo !== null) {
            $logoPath = $settings?->system_logo;
            $publicPath = is_string($logoPath) && $logoPath !== ''
                ? public_path('storage/'.$logoPath)
                : null;

            if ($publicPath !== null && Brand::isUsableLogoFile($publicPath)) {
                $mime = mime_content_type($publicPath) ?: 'image/png';

                return response(
                    file_get_contents($publicPath) ?: '',
                    HttpResponse::HTTP_OK,
                    ['Content-Type' => $mime],
                );
            }
        }

        return response(
            file_get_contents(public_path('favicon.svg')) ?: '',
            HttpResponse::HTTP_OK,
            ['Content-Type' => 'image/svg+xml'],
        );
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
        $icon = Brand::defaultLogoUrl();

        return [
            [
                'src' => $icon,
                'sizes' => '192x192',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
            ],
            [
                'src' => $icon,
                'sizes' => '512x512',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
            ],
        ];
    }
}
