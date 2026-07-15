<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'current-organization' => \App\Http\Middleware\SetCurrentOrganization::class,
        ]);

        // Render (like most PaaS hosts) terminates TLS at its own edge
        // and forwards plain HTTP to the container, with the original
        // scheme passed via X-Forwarded-Proto. Without trusting that
        // header, Laravel thinks every request is insecure and
        // generates http:// URLs (login form action, asset URLs...),
        // which browsers then block as a mixed-content/insecure submit.
        // Render's edge IPs aren't published/fixed, so trust the proxy
        // headers from any source (safe here: the container is not
        // reachable directly from the internet, only via Render's edge).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
