<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Throwable;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        TokenInvalidException::class,
        TokenExpiredException::class,
        JWTException::class,
        \Nuwave\Lighthouse\Exceptions\AuthenticationException::class,
    ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($request->wantsJson() || $request->is('graphql*')) {
            // Handle GraphQL specific authentication exceptions
            if ($e instanceof \Nuwave\Lighthouse\Exceptions\AuthenticationException) {
                return response()->json([
                    'errors' => [
                        [
                            'message' => 'Authentication required',
                            'extensions' => [
                                'code' => 4013,
                                'category' => 'authentication'
                            ]
                        ]
                    ],
                    'data' => null
                ], 401);
            }
            
            // JWT specific exceptions
            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'code' => 4010,
                    'message' => 'Unauthorized. Invalid token.'
                ], 401);
            }
            
            if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'code' => 4011,
                    'message' => 'Unauthorized. Token has expired.'
                ], 401);
            }
            
            if ($e instanceof JWTException) {
                return response()->json([
                    'code' => 4012,
                    'message' => 'Unauthorized. Token is missing or malformed.'
                ], 401);
            }
            
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'code' => 4013,
                    'message' => 'Unauthorized. Authentication required.'
                ], 401);
            }
        }

        return parent::render($request, $e);
    }
}