<?php
namespace App\Services;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class AuthService{
    public static function Auth()
    {
        if (auth('api')->check()) {
            return auth('api')->user();
        }
        return null;
    }

    public static function getToken(){
        try {
            return JWTAuth::getToken();
        } catch (JWTException $e) {
            Log::warning('AuthService: Could not get token.', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    public static function isAdmin(){
        $user = self::Auth();
        if($user && $user->role === 'admin'){
            return true;
        }
        return false;
    }
}