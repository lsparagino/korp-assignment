<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v0',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);
        $middleware->alias([
            'company' => \App\Http\Middleware\EnsureUserBelongsToCompany::class,
            'idempotent' => \App\Http\Middleware\EnsureIdempotency::class,
        ]);
    })->withExceptions(function (\Illuminate\Foundation\Configuration\Exceptions $exceptions): void {
        // Required by Laravel to register the ExceptionHandler binding.
        // Custom exception rendering/reporting can be added here as needed.
    })->create();
