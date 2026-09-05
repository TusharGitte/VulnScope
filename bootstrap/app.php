<?php

use App\Http\Middleware\EnsureStepOrder;
use App\Http\Middleware\EnsureProjectAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'step' => EnsureStepOrder::class,
            'project.access' => EnsureProjectAccess::class,
        ]);

        // Security headers on every response (belt-and-braces on top of any web-server config)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->encryptCookies();
        $middleware->validateCsrfTokens();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\App\Exceptions\ScopeViolationException $e, $request) {
            return response()->view('errors.scope-blocked', ['message' => $e->getMessage()], 403);
        });
    })->create();
