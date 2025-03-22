<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use http\Message;
use Tymon\JWTAuth\Contracts\Providers\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

final readonly class AuthResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args) {}
    public function getUserInfo($_, array $args)
    {
        JWTAuth::setToken($args['jwt']);
        if (!$user = JWTAuth::check())
            return [
                'code' => 401,
                'message' => 'Unauthorized/invalid token',
                'user' => null
            ];
        return [
            'code' => 200,
            'message' => 'success',
            'user' => $user
        ];
    }
    public function check($_, array $args): array
    {
        return ['message' => $args['message']];
    }
    public function checkConnection($_, array $args): array
    {
       try {
        // Check MySQL connection
        \DB::connection('mysql')->getPdo();

        // Check MongoDB connection
        \DB::connection('mongodb')->getMongoClient();

        return [
            'code' => 200,
            'message' => 'success',
        ];
    } catch (\Exception $e) {
        return [
            'code' => 500,
            'message' => 'Database connection failed: ' . $e->getMessage(),
        ];
    }
    }
    public function getTokenState($_, array $args): array
    {


        try {
            JWTAuth::setToken($args['jwt']);
            if (JWTAuth::check()) {
                return [
                    'code' => 200,
                    'message' => "token valid",
                    'token' => null,
                ];
            }
            return
                [
                    'code' => 401,
                    'message' => 'token invalid',
                    'token' => null,
                ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => 'internal server error',
                'token' => null,
            ];
        }
    }
}
