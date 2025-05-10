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
            if ($e instanceof \Nuwave\Lighthouse\Exceptions\AuthenticationException) {
                return response()->json([
                    'code' => 4010,
                    'message' => 'Unauthorized. Authentication Exception.'
                ], 200);// Return 200 so GraphQL client gets the data payload
            }
            
            // JWT specific exceptions
            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'code' => 4011,
                    'message' => 'Unauthorized. Invalid token.'
                ], 200);
            }
            
            if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'code' => 4012,
                    'message' => 'Unauthorized. Token has expired.'
                ], 200);
            }
            
            if ($e instanceof JWTException) {
                return response()->json([
                    'code' => 4013,
                    'message' => 'Unauthorized. Token is missing or malformed.'
                ], 200);
            }
            
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'code' => 4014,
                    'message' => 'Unauthorized. Authentication required.'
                ], 200);
            }
        

        return parent::render($request, $e);
    }
}