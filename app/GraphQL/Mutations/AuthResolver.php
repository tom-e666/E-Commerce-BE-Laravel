<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\UserCredential;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\GraphQL\Traits\GraphQLResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\EmailVerificationService;


final readonly class AuthResolver
{

    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
        //can I call middleware here?
    }
    
    public function signup($_, array $args){
        $validator = Validator::make($args, [
            'email' => 'required|string|email|unique:user_credentials',
            'phone' => 'required|string|unique:user_credentials',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        try {
            $user = UserCredential::create([
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
    public function login($_, array $args){
        $credentials = ['email' => $args['email'], 'password' => $args['password']];
        
        if ( !$token = JWTAuth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }
        $user = auth('api')->user();

        $refreshToken = Str::random(60);

        $existingToken = DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->first();

        if ($existingToken) {
            DB::table('refresh_tokens')
                ->where('user_id', $user->id)
                ->update([
                    'token' => $refreshToken,
                    'expires_at' => now()->addDays(7),
                ]);
        } else {
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
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'role' => $user->role,  // Added role field
                'email_verified' => $user->email_verified,
            ],
        ], 'Login successful');
    }

    public function logout($_, array $args)
    {
        //unblock after adding widdleware authentication
        // $token = JWTAuth::getToken();
        // $payload = JWTAuth::getPayload($token);
        // $exp = $payload['exp'];
        // $ttl = $exp - time();

        // Cache::put('blacklist' . $token, true, $ttl);

        if(isset($args['refresh_token']))
        {
            DB::table('refresh_tokens')
                ->where('token', $args['refresh_token'])
                ->delete();
        }
        return $this->success(null, 'Logout successful');
    }
    public function refreshToken($_, $args){
        $refreshToken = $args['refresh_token'];
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
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'role' => $user->role,  // Added role field
                'email_verified' => $user->email_verified,
            ],
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

    public function sendVerificationEmail($_, $args)
    {
        $user = auth('api')->user();

        $emailService = app(EmailVerificationService::class);
        $emailService->sendVerificationEmail($user);

        return $this->success([
            'user' => $user,
        ], 'Verification email sent successfully', 200);
    }
    public function verifyEmail($_, $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        if ($user->email_verified) {
            return $this->error('Email already verified', 422);
        }
        // Verify the email address
        $user->email_verified = true;
        $user->save();

        return $this->success([
            'user' => $user,
        ], 'Email verified successfully', 200);
    }
}
