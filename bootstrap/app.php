<?php

use App\Enums\Appearance;
use App\Http\Middleware\EnsureOrganizationSetup;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Log\LogLevel;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Written by the browser, so it cannot carry Laravel's encryption.
        $middleware->encryptCookies(except: [Appearance::COOKIE]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'organization.setup' => EnsureOrganizationSetup::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Passport reports every rejected bearer token, and most are access
        // tokens that expired an hour in; the client refreshes and retries.
        // Keep them in the logs but out of the error alerts.
        $exceptions->level(OAuthServerException::class, LogLevel::WARNING);
    })->create();
