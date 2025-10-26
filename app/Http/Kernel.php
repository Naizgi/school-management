<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Global HTTP middleware stack.
     * These middleware run during every request to your application.
     */
    protected $middleware = [
        // Handles Cross-Origin Resource Sharing
        \Illuminate\Http\Middleware\HandleCors::class,

        // Common Laravel middlewares
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * Route Middleware Groups
     */
    protected $middlewareGroups = [

        // --- For API routes ---
        'api' => [
            // Apply throttling limits for API requests
            'throttle:api',

            // Handle route-model bindings
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

            // Use JWT authentication for API routes
            \Tymon\JWTAuth\Middleware\Authenticate::class,

            // If you're using Laravel Passport too, uncomment next line:
            // \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,
        ],

        // --- For web routes ---
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Route middleware that can be assigned individually.
     */
    protected $routeMiddleware = [
        // JWT authentication for API endpoints
        'jwt.auth' => \Tymon\JWTAuth\Middleware\Authenticate::class,

        // Handle guest users
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,

        // Throttling middleware
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        // Ensure authenticated user for private Reverb channels
        'auth' => \App\Http\Middleware\Authenticate::class,
    ];
}
