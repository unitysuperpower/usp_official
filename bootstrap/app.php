<?php

use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\SearchRobots;
use App\Http\Middleware\TrackPageViews;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => IsAdmin::class,
        ]);
        $middleware->append(TrackPageViews::class);
        $middleware->append(SearchRobots::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
