<?php

use Domain\Driver\Exceptions\DriverNotFoundException;
use Domain\Driver\Providers\DriverServiceProvider;
use Domain\Order\Exceptions\NoAvailableDriverException;
use Domain\Order\Exceptions\OrderAlreadyAssignedException;
use Domain\Order\Exceptions\OrderNotFoundException;
use Domain\Order\Providers\OrderServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Presentation\Api\Providers\ApiServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        OrderServiceProvider::class,
        DriverServiceProvider::class,
        ApiServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (OrderNotFoundException $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (DriverNotFoundException $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (OrderAlreadyAssignedException $e) => response()->json(['message' => $e->getMessage()], 409));
        $exceptions->render(fn (NoAvailableDriverException $e) => response()->json(['message' => $e->getMessage()], 422));
    })->create();
