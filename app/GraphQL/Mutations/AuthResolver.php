<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

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

    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
        //can I call middleware here?
    }
    // public function signup($_, array $args): array
    // {
    //     $userData = $args['user'];
    //     if(empty($userData['username']) && empty($userData['email']) && empty($userData['phone']))
    //     {
    //         return [
    //             'code'=> 400,
    //             'message'=>@'at least username|email|phone is provided',
    //             'token'=> null, 
    //         ];
    //     }
    //     if(empty($userData['username']))
    //     {
    //         if(!empty($userData['email']))
    //         {
    //             $userData['username'] = $userData['email'];
    //         }else
    //         {
    //             $userData['username'] = $userData['phone'];
    //         }
    //     }
    //     $rules= [
    //         'password'=>'string|min:8|required ',
    //     ];
        
    //     if(!empty($userData['username']))
    //     {
    //         $rules['username'] = 'string|unique:user_credentials';
    //     }
    //     if(!empty($userData['email']))
    //     {
    //         $rules['email'] = 'string|email|unique:user_credentials';

    //     }
    //     if(!empty($userData['phone']))
    //     {
    //         $rules['phone'] = 'string|unique:user_credentials';
    //     }
        
    //     $validator = Validator::make($userData, $rules);
        
    //     if ($validator->fails()) 
    //         return [
    //             'code' => 400,
    //             'message' => $validator->errors(),
    //             'token' => null,
    //         ];
    
        
    //     $user = [
    //         'password'=>Hash::make($userData['password']),
    //         'email_verified'=>false,
    //         'phone_verified'=>false,
    //     ];
    //     if(!empty($userData['username']))
    //     {
    //         $user['username'] = $userData['username'];
    //     }
    //     if(!empty($userData['email']))
    //     {
    //         $user['email'] = $userData['email'];
        
    //     }
    //     if(!empty($userData['phone']))
    //     {
    //         $user['phone'] = $userData['phone'];
    //     }
    //     $userCredInstance = UserCredential::create($user);
    //     $token = JWTAuth::fromUser($userCredInstance);
    //     return [
    //         'code' => 200,
    //         'message' => 'success',
    //         'token' => $token,
    //     ];
    // }

    public function signup($_, array $args){
        $validator = Validator::make($args, [
            'username' => 'required|string|unique:user_credentials',
            'email' => 'required|string|email|unique:user_credentials',
            'phone' => 'required|string|unique:user_credentials',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string',
            // 'role' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        try {
            $user = UserCredential::create([
                'username' => $args['username'],
                'email' => $args['email'],
                'phone' => $args['phone'],
                'password' => Hash::make($args['password']),
                'full_name' => $args['full_name'],
                'role' => 'user',
            ]);
        } catch (\Exception $e) {
            return $this->error('An error occurred: ' . $e->getMessage(), 500);
        }

        return $this->success([
            'user' => $user,
        ], 'User created successfully', 200);
    }
 
// public function login($_, array $args): array
// {
//     $userData = $args['user'];
//     $identifier = isset($userData['email']) ? 'email' : (isset($userData['phone']) ? 'phone' : 'username');
//     $value = $userData[$identifier];
//     $credentials = [
//         $identifier => $value,
//         'password' => $userData['password']
//     ];

//     $user = UserCredential::where($identifier, $value)->first();

//     if (!$user) {
//         return [
//             'code' => 401,
//             'message' => 'User not found',
//             'token' => null,
//         ];
//     }

//     if (!Hash::check($userData['password'], $user->password)) {
       
//         return [
//             'code' => 401,
//             'message' => "Invalid password. Stored hash: {$user->password}",
//             'token' => null,
//         ];
//     }

//     if ($token = JWTAuth::attempt($credentials)) {
//         return [
//             'code' => 200,
//             'message' => 'success',
//             'token' => $token,
//         ];
//     }

//     return [
//         'code' => 401,
//         'message' => 'invalid credentials',
//         'token' => null,
//     ];
// }

    public function login($_, array $args){
        $credentials = ['email' => $args['email'], 'password' => $args['password']];
        
        if ( !$token = JWTAuth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }

        $user = auth()->user();
        $refreshToken = Str::random(60);

        // Check if the user already has a refresh token
        $existingToken = DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->first();
        if ($existingToken) {
            // Update the existing refresh token
            DB::table('refresh_tokens')
                ->where('user_id', $user->id)
                ->update([
                    'token' => $refreshToken,
                    'expires_at' => now()->addDays(7),
                ]);
        } else {
            // Create a new refresh token
            DB::table('refresh_tokens')->insert([
                'user_id' => $user->id,
                'token' => $refreshToken,
                'expires_at' => now()->addDays(7),
            ]);
        }

        return $this->success([
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'expires_at' => JWTAuth::factory()->getTTL() * 60,
        ], 'Login successful');
    }

    // public function logout($_, array $args)
    // {

    //     try {
    //         JWTAuth::logout();
    //         return [
    //             'code' => 200,
    //             'message' => 'success',
    //             'token' => null,
    //         ];
    //     } catch (\Exception $e) {
    //         return [
    //             'code' => 500,
    //             'message' => 'error',
    //             'token' => null,
    //         ];
    //     }
    // }

    public function logout($_, array $args)
    {
        // $refreshToken = $args['refresh_token'];
        // // hashed token
        // // $hashed = hash('sha256', $refreshToken);

        // // DB::table('refresh_tokens')
        // //     ->where('token', $hashed)
        // //     ->delete();

        // DB::table('refresh_tokens')
        //     ->where('token', $refreshToken)
        //     ->delete();

        // JWTAuth::invalidate(JWTAuth::getToken());

        // return $this->success(null, 'Logout successful');
        $token = JWTAuth::getToken();
        $payload = JWTAuth::getPayload($token);
        $exp = $payload['exp'];
        $ttl = $exp - time();

        Cache::put('blacklist' . $token, true, $ttl);

        if(isset($args['refresh_token']))
        {
            DB::table('refresh_tokens')
                ->where('token', $args['refresh_token'])
                ->delete();
        }
        
        return $this->success(null, 'Logout successful');
    }

    // public function refreshToken($_, array $args)
    // {

    //     //try to authenticate
    //     //try to refesh?
    //     //expired?
    //     //require login
    //     try {
    //         JWTAuth::setToken($args['jwt']);
    //         $newToken = JWTAuth::refresh();
    //         return [
    //             'code' => 200,
    //             'message' => 'success',
    //             'token' => $newToken,
    //         ];
    //     } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
    //         try {
    //             $newToken = JWTAuth::refresh();
    //             return [
    //                 'code' => 200,
    //                 'message' => 'Expired token refreshed successfully',
    //                 'token' => $newToken,
    //             ];
    //         } catch (\Exception $innerException) {
    //             return [
    //                 'code' => 401,
    //                 'message' > 'Token expired, consider re-fetch',
    //                 'token' => null,
    //             ];
    //         }
    //     } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
    //         return [
    //             'code' => 401,
    //             'message' => 'Invalid token',
    //             'token' => null,
    //         ];
    //     } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
    //         return [
    //             'code' => 401,
    //             'message' => 'Token blacklisted',
    //             'token' => null,
    //         ];
    //     } catch (\Exception $e) {
    //         return [
    //             'code' => 500,
    //             'message' => 'Internal server error, or ' . $e->getMessage(),
    //             'token' => null,
    //         ];
    //     }
    // }

    public function refreshToken($_, $args){
        $refreshToken = $args['refresh_token'];
        //hashed token
        // $hashed = hash('sha256', $refreshToken);

        // $record = DB::table('refresh_tokens')
        //     ->where('token', $hashed)
        //     ->where('expires_at', '>', now())
        //     ->first();

        $record = DB::table('refresh_tokens')
            ->where('token', $refreshToken)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return $this->error('Invalid or expired refresh token', 401);
        }

        $user = UserCredential::find($record->user_id);
        $newAccessToken = JWTAuth::fromUser($user);

        DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->update([
                'token' => $refreshToken,
                'expires_at' => now()->addDays(7),
            ]);

        return $this->success([
            'access_token' => $newAccessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => JWTAuth::factory()->getTTL() * 60,
        ], 'Token refreshed successfully');
    }

    public function invalidateToken($_, $args)
    {
        $currentToken = $args['jwt'];
        JWTAuth::setToken($currentToken);

        if (JWTAuth::check()) {
            try {
                $newToken = JWTAuth::invalidate();
                return [
                    'code' => 200,
                    'message' => "token have valid state, successfully invalidated",
                    'token' => null,
                ];
            } catch (\Exception $e) {
                return [
                    'code' => 401,
                    'message' => "error trying to invalidate token" . "\n" . ($e->getMessage()),
                    'token' => null,
                ];
            }
        } else {
            return [
                'code' => 401,
                'message' => "invalid token",
                'token' => null,
            ];
        }
    }
}
