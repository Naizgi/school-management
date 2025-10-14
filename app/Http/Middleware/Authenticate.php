<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $this->authenticate($request, $guards);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            // For API requests, always return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                Log::warning('Authentication failed for API request', [
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'message' => 'Unauthenticated. Please provide a valid token.',
                    'error' => 'authentication_failed'
                ], 401);
            }
            
            throw $e;
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API requests or when using JWT guard, NEVER redirect to login
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // Only redirect for web routes (if you have any)
        return route('login');
    }
}