<?php
namespace App\Services;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class AuthService{
    public static function Auth(){
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                Log::warning('AuthService: User not found from token, though authenticate() did not throw.');
                return null; 
            }
            return $user;
        } catch (TokenExpiredException $e) {
            Log::error('AuthService: Token expired.', ['exception' => $e->getMessage()]);
            return null;
        } catch (TokenInvalidException $e) {
            Log::error('AuthService: Token invalid.', ['exception' => $e->getMessage()]);
            return null;
        } catch (JWTException $e) {
            Log::error('AuthService: JWTException.', ['exception' => $e->getMessage()]);
            return null;
        } catch (\Exception $e) {
            Log::error('AuthService: Unexpected exception during authentication.', ['exception' => $e->getMessage()]);
            return null;
        }
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