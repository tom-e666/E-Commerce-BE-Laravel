<?php

namespace App\Services;

use App\Models\UserCredential;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\VerifyEmail;

class EmailVerificationService
{
    // public function __construct(EmailVerificationService $emailVerificationService)
    // {
    //     $this->emailVerificationService = $emailVerificationService;
    // }
    public function register($_, array $args)
    {
        // Your existing validation and user creation...
        
        // After creating user
        $user = UserCredential::create([
            'email' => $args['email'],
            'password' => Hash::make($args['password']),
            'full_name' => $args['full_name'],
            'phone' => $args['phone'] ?? null,
            'email_verified' => false,
            'role' => UserCredential::ROLE_USER,
        ]);
        
        // Send verification email
        $this->emailVerificationService->sendVerificationEmail($user);
        
        return $this->success([
            'user' => $user,
            'token' => $this->generateToken($user)
        ], 'Registration successful. Please verify your email address.', 201);
    }
    /**
     * Generate a verification URL for the given user.
     *
     * @param  UserCredential $user
     * @return string
     */
    public function generateVerificationUrl(UserCredential $user)
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));
        
        // Generate a signed URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->email),
            ]
        );
        
        // Convert Laravel's verification URL to your frontend URL
        $verificationUrl = str_replace(url('/api'), $frontendUrl, $verificationUrl);
        
        return $verificationUrl;
    }
    
    /**
     * Send email verification notification.
     *
     * @param  UserCredential $user
     * @return void
     */
    public function sendVerificationEmail(UserCredential $user)
    {
        try {
            if ($user->email_verified) {
                return;
            }
            
            $verificationUrl = $this->generateVerificationUrl($user);
            
            $userData = [
                'name' => $user->full_name,
                'email' => $user->email,
            ];
            
            Mail::to($user->email)->send(new VerifyEmail($userData, $verificationUrl));
            
        } catch (\Exception $e) {
            \Log::error('Email verification error: ' . $e->getMessage());
            throw new \Exception('Failed to send verification email: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify the email for the given user.
     *
     * @param int $userId
     * @param string $hash
     * @return bool
     */
    public function verifyEmail($userId, $hash)
    {
        $user = UserCredential::findOrFail($userId);
        
        // Check if email is already verified
        if ($user->email_verified) {
            return true;
        }
        
        // Check if the hash matches
        if (sha1($user->email) != $hash) {
            return false;
        }
        
        $user->email_verified = true;
        $user->email_verified_at = Carbon::now();
        $user->save();
        
        return true;
    }
    
    /**
     * Resend the verification email to the user.
     *
     * @param UserCredential $user
     * @return bool
     */
    public function resend(UserCredential $user)
    {
        if ($user->email_verified) {
            return false;
        }
        
        $this->sendVerificationEmail($user);
        return true;
    }
}