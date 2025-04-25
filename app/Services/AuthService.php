<?php

namespace App\Services;

use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService{
    public static function Auth(){
        return JWTAuth::parseToken()->authenticate();
    }
    public static function isAdmin(){
        $user = self::Auth();
        if($user && $user->role === 'admin'){
            return true;
        }
        return false;
    }
}