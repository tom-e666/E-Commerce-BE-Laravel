<?php

namespace App\Services;

use App\Models\UserCredential;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\ResetPassword;

class PasswordResetService
{
    /**
     * Create a password reset token for the user
     *
     * @param string $email
     * @return bool|string Returns token if successful, false otherwise
     */
    public function createToken(string $email)
    {
        $user = UserCredential::where('email', $email)->first();
        
        if (!$user) {
            return false;
        }
        
        // Create a new token
        $token = Str::random(60);
        
        // Store the token in user model using the existing email verification fields
        $user->email_verification_token = $token;
        $user->email_verification_sent_at = now();
        $user->save();
        
        return $token;
    }
    
    /**
     * Send password reset email
     *
     * @param string $email
     * @return bool
     */
    public function sendResetEmail(string $email)
    {
        $token = $this->createToken($email);
        
        if (!$token) {
            return false;
        }
        
        $user = UserCredential::where('email', $email)->first();
        
        // Generate the URL
        $resetUrl = $this->generateResetUrl($user->id, $token);
        
        // Send the email
        $userData = [
            'name' => $user->full_name ?: 'Khách Hàng',
            'email' => $user->email,
        ];
        
        Mail::to($user->email)->send(new ResetPassword($userData, $resetUrl));
        
        return true;
    }
    
    /**
     * Generate a reset URL for the given user
     *
     * @param int $userId
     * @param string $token
     * @return string
     */
    private function generateResetUrl($userId, $token)
    {
        $frontendUrl = config('app.frontend_url');
        $frontendUrl = env('FRONTEND_URL', $frontendUrl);
        
        return "{$frontendUrl}/forgotPassword/{$userId}/{$token}";
    }
    
    /**
     * Validate the token and reset password
     *
     * @param int $userId
     * @param string $token
     * @param string $newPassword
     * @return bool
     */
    public function resetPassword($userId, $token, $newPassword)
    {
        $user = UserCredential::where('id', $userId)
            ->where('email_verification_token', $token)
            ->first();
        
        if (!$user) {
            return false;
        }
        
        // Check if token is expired (default: 1 hour)
        if ($user->email_verification_sent_at->addHour()->isPast()) {
            return false;
        }
        
        // Update user password
        $user->password = Hash::make($newPassword);
        $user->email_verification_token = null; // Clear the token
        $user->email_verification_sent_at = null; // Clear the timestamp
        $user->save();
        
        return true;
    }
    
    /**
     * Verify the token without resetting the password
     *
     * @param int $userId
     * @param string $token
     * @return bool
     */
    public function verifyToken($userId, $token)
    {
        $user = UserCredential::where('id', $userId)
            ->where('email_verification_token', $token)
            ->first();
        
        if (!$user) {
            return false;
        }
        
        // Check if token is expired (default: 1 hour)
        if ($user->email_verification_sent_at->addHour()->isPast()) {
            return false;
        }
        
        return true;
    }
}
