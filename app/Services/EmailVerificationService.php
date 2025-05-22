<?php

namespace App\Services;

use App\Models\UserCredential;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\VerifyEmail;
use Illuminate\Support\Str;

class EmailVerificationService
{

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
        ], 'Đăng ký thành công. Vui lòng xác minh địa chỉ email của bạn.', 201);
    }
    /**
     * Generate a verification URL for the given user.
     *
     * @param  UserCredential $user
     * @return string
     */
    public function generateVerificationUrl(UserCredential $user)
    {
        $frontendUrl = config('app.frontend_url');
        $frontendUrl = env('FRONTEND_URL', $frontendUrl);


        $token = hash_hmac('sha256', $user->email . Str::random(40), config('app.key'));
        
        $user->email_verification_token = $token;
        $user->email_verification_sent_at = now();
        $user->save();
        
        return "{$frontendUrl}/email/verify/{$token}";
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
                'name' => $user->full_name ?: 'Khách Hàng',
                'email' => $user->email,
            ];
            
            // This line creates the connection to the email view through the VerifyEmail mailable
            Mail::to($user->email)->send(new VerifyEmail($userData, $verificationUrl));
            
            // Update the user's record to track when verification email was sent
            $user->email_verification_sent_at = now();
            $user->save();
            
        } catch (\Exception $e) {
            \Log::error('Lỗi xác minh email: ' . $e->getMessage());
            throw new \Exception('Không thể gửi email xác minh: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify the email for the given user.
     *
     * @param string $token
     * @return bool
     */
    public function verifyEmail($token)
    {
        // Find user by token
        $user = UserCredential::where('email_verification_token', $token)->first();
        
        if (!$user) {
            return false;
        }
        
        // Check if email is already verified
        if ($user->email_verified) {
            return true;
        }
        
        // Check if token is expired (token valid for 24 hours)
        // Handle case where email_verification_sent_at might be a string or null
        if ($user->email_verification_sent_at) {
            $sentAt = $user->email_verification_sent_at instanceof Carbon 
                ? $user->email_verification_sent_at 
                : Carbon::parse($user->email_verification_sent_at);
                
            if ($sentAt->addHours(24)->isPast()) {
                return false;
            }
        }
        
        // Mark email as verified
        $user->email_verified = true;
        $user->email_verified_at = Carbon::now();
        $user->email_verification_token = null; // Clear the token after use
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