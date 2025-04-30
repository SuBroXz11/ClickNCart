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
        // Register your middleware aliases
        $middleware->alias([
            'auth' => \App\Http\Middleware\JwtMiddleware::class,
            'jwt.verify' => \App\Http\Middleware\JwtMiddleware::class,
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class, 
        ]);

        // Add global middleware for web
        $middleware->web([
            
            // \Illuminate\Cookie\Middleware\EncryptCookies::class,
            // \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Add global middleware for API
        $middleware->api([
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling
        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired'], 401);
        });
        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        });
        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Error processing token'], 401);
        });
    })->create();