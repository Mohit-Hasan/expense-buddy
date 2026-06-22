<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('expensebuddy:prune-expired-invoices')->daily();
        $schedule->command('expensebuddy:run-scheduled-backup')->everyMinute();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->web(append: [
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\EnsureSessionVersion::class,
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdministrator::class,
            'menu.permission' => \App\Http\Middleware\EnsureMenuPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                app(\App\Services\RouteErrorTracker::class)->record(
                    $response->getStatusCode(),
                    '/'.$request->path()
                );
            }

            return $response;
        });
    })->create();
