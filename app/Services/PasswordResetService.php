<?php

namespace App\Services;

use App\Models\UserCredential;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
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
        
        // Delete any existing tokens for this user
        DB::table('password_reset_tokens')
            ->where('user_id', $user->id)
            ->delete();
        
        // Create a new token
        $token = Str::random(60);
        
        // Store the token in database
        DB::table('password_reset_tokens')->insert([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addHours(1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
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
        
        return "{$frontendUrl}/reset-password/{$userId}/{$token}";
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
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('user_id', $userId)
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$tokenRecord) {
            return false;
        }
        
        $user = UserCredential::find($userId);
        if (!$user) {
            return false;
        }
        
        // Update user password
        $user->password = Hash::make($newPassword);
        $user->save();
        
        // Delete token after successful password reset
        DB::table('password_reset_tokens')
            ->where('user_id', $userId)
            ->delete();
        
        return true;
    }
}
