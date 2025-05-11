<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\UserCredential;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\GraphQL\Traits\GraphQLResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;



final readonly class AuthResolver
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args) {}
    public function getUserInfo($_, array $args)
    {

        $user =AuthService::Auth();
        if (!$user)
            return [
                'code' => 401,
                'message' => 'Unauthorized',
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
    public function checkConnection($_, array $args)
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
    
    public function getUserByJWT($_, array $args): array
    {
        try {
            $user = AuthService::getUserByJWT($args['jwt']);
            if ($user) {
                return [
                    'code' => 200,
                    'message' => 'success',
                    'user' => $user
                ];
            }
            
            return [
                'code' => 401,
                'message' => 'No user found',
                'user' => null
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => 'Error retrieving user: ' . $e->getMessage(),
                'user' => null
            ];
        }
    }
    public function getUserCredential($_, array $args): array
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return [
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'user' => null
                ];
            }
            return [
                'code' => 200,
                'message' => 'success',
                'user' => $user
            ];
        } catch (\Exception $e) {
            return [
                'code' => 401,
                'message' => "Failed to obtain user",
                'user' => null
            ];
        }
    }
}
