<?php

declare(strict_types=1);

namespace App\Providers;

use BladeUI\Icons\Factory;
use Illuminate\Support\ServiceProvider;

class MingCuteIconsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->callAfterResolving(Factory::class, function (Factory $factory): void {
            $svgDir = resource_path('svg/mingcute');

            $factory->add(
                set: 'mingcute-icons',
                options: [
                    'prefix' => 'mingcute',
                    'path' => $svgDir,
                    'paths' => null,
                ]
            );
        });
    }
}
