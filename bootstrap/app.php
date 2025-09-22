<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure( basePath: dirname( __DIR__ ) )
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware( function (Middleware $middleware): void {
        $middleware->alias( [ 
            'tenancy'         => \Stancl\Tenancy\Middleware\InitializeTenancyByPath::class,
            'tenancy.prevent' => \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
        ] );

    } )
    ->withExceptions( function (Exceptions $exceptions): void {
        //
    } )->create();