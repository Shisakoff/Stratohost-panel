<?php

use App\Http\Middleware\AuthenticateAgentCallback;
use App\Http\Middleware\EnsureRootAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Lets the Vue SPA (served from the same origin) authenticate to
        // /api/* with the normal session cookie instead of API tokens.
        $middleware->statefulApi();

        $middleware->alias([
            'root_admin' => EnsureRootAdmin::class,
            'agent_token' => AuthenticateAgentCallback::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
