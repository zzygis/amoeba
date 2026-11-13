<?php

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
    ->withMiddleware(function (Middleware $middleware) {
        // Render terminates HTTPS at its proxy and forwards plain HTTP to the container.
        // Trusting the proxy lets Laravel see the original https:// scheme and client IP
        // (the client IP matters for the login rate limiter).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // This is an API-only app: every error under /api is returned as JSON
        // ({"message": "...", "errors": {...}}), even if the client forgot the
        // "Accept: application/json" header. No HTML error pages, no redirects to a login page.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
    })->create();
