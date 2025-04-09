<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // Global middlewares (if any)
        \Illuminate\Http\Middleware\HandleCors::class,
    ];

    protected $middlewareGroups = [
        'api' => [
            \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // Add the JWT authentication middleware here
            \Tymon\JWTAuth\Middleware\Authenticate::class, // Make sure you have this line
        ],
    ];

    protected $routeMiddleware = [
        // You can also define this middleware here for specific routes
        'jwt.auth' => \Tymon\JWTAuth\Middleware\Authenticate::class,
    ];
}
