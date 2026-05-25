<?php

use App\Http\Middleware\EnsureMarketplaceConfigured;
use App\Http\Middleware\EnsurePlanAllowsFeature;
use App\Http\Middleware\EnsureSubscribed;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetLocaleFromSession;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'feature' => EnsurePlanAllowsFeature::class,
            'admin' => IsAdmin::class,
        ]);

        // Add localization middleware to API routes
        $middleware->api(append: [
            SetLocale::class,
        ]);

        // Add localization middleware and Cirotik locks to web routes
        $middleware->web(append: [
            SetLocaleFromSession::class,
            EnsureSubscribed::class,
            EnsureMarketplaceConfigured::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
