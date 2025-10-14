<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

// Add these JWT-specific imports
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        TokenExpiredException::class,
        TokenInvalidException::class,
        JWTException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (Throwable $e, $request) {
            // Always return JSON for API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e, $request);
            }
        });

        // You can also add specific reporting for JWT exceptions
        $this->reportable(function (TokenExpiredException $e) {
            \Log::warning('JWT Token expired', ['exception' => $e]);
        });

        $this->reportable(function (TokenInvalidException $e) {
            \Log::warning('JWT Token invalid', ['exception' => $e]);
        });
    }

    /**
     * Handle API exceptions with JWT support
     */
    protected function handleApiException(Throwable $e, $request)
    {
        // Handle JWT Token Expired
        if ($e instanceof TokenExpiredException) {
            return response()->json([
                'message' => 'Authentication token has expired',
                'error' => 'token_expired',
                'suggestion' => 'Use the refresh token endpoint to get a new token'
            ], 401);
        }

        // Handle JWT Token Invalid
        if ($e instanceof TokenInvalidException) {
            return response()->json([
                'message' => 'Authentication token is invalid',
                'error' => 'token_invalid',
                'suggestion' => 'Please login again to get a new token'
            ], 401);
        }

        // Handle JWT Token Blacklisted
        if ($e instanceof TokenBlacklistedException) {
            return response()->json([
                'message' => 'Authentication token has been blacklisted',
                'error' => 'token_blacklisted',
                'suggestion' => 'Please login again to get a new token'
            ], 401);
        }

        // Handle general JWT exceptions
        if ($e instanceof JWTException) {
            return response()->json([
                'message' => 'Authentication token is missing or invalid',
                'error' => 'token_absent',
                'suggestion' => 'Please include a valid Authorization header'
            ], 401);
        }

        // Handle validation errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        // Handle model not found (Eloquent)
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return response()->json([
                'message' => "$model not found",
                'error' => 'resource_not_found'
            ], 404);
        }

        // Handle route not found
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Endpoint not found',
                'error' => 'endpoint_not_found',
                'path' => $request->path()
            ], 404);
        }

        // Handle wrong HTTP method
        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'message' => 'HTTP method not allowed for this endpoint',
                'error' => 'method_not_allowed',
                'allowed_methods' => $e->getHeaders()['Allow'] ?? []
            ], 405);
        }

        // Handle unauthorized access
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated. Please provide a valid authentication token.',
                'error' => 'unauthenticated'
            ], 401);
        }

        // Handle other generic HTTP errors
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $message = $e->getMessage() ?: 'HTTP error occurred';
            
            return response()->json([
                'message' => $message,
                'error' => 'http_error',
                'status_code' => $statusCode
            ], $statusCode);
        }

        // Fallback for all unhandled errors
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        // Detailed error in development, generic in production
        if (config('app.debug')) {
            return response()->json([
                'message' => 'Server Error',
                'error' => 'server_error',
                'exception' => get_class($e),
                'message_detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ], $statusCode);
        }

        return response()->json([
            'message' => 'Server Error',
            'error' => 'server_error'
        ], $statusCode);
    }

    /**
     * Convert an authentication exception into a response.
     * Override the default to prevent redirects for API.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Always return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthenticated. Please provide a valid authentication token.',
                'error' => 'unauthenticated'
            ], 401);
        }

        // Only redirect for web routes
        return redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}