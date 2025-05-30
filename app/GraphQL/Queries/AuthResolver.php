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
        try{
            \DB::connection('mysql')->getPdo();
        }
        catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => 'MySQL connection failed: ' . $e->getMessage(),
            ];
        }
        try {
            \DB::connection('mongodb')->getMongoClient();
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => 'MongoDB connection failed: ' . $e->getMessage(),
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
        ];
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
}
