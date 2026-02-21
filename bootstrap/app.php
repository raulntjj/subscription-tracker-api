<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Modules\Shared\Infrastructure\Auth\Middleware\Authenticate;
use Modules\Shared\Infrastructure\Locale\Middleware\SetLocaleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../modules/Shared/Interface/Routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            SetLocaleMiddleware::class,
        ]);

        $environment = $_ENV['APP_ENV'] ?? 'production';
        if ($environment === 'production') {
            $middleware->api(append: [
                ThrottleRequests::class,
            ]);
        }

        $middleware->alias([
            'auth.jwt' => Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
