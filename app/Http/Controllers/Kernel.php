<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Http\Middleware\SetCacheHeaders::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        // \Illuminate\Session\Middleware\StartSession::class,
        // \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        // \Illuminate\Auth\Middleware\AuthenticateSession::class,
        
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'api' => [
            'throttle:60,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        'auth.throttle' => [
            'throttle:5,1',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.api' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        // 'check.blacklist' => \App\Http\Middleware\CheckBlacklistToken::class,
    ];
}
