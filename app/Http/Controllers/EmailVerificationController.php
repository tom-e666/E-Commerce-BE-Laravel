<?php

namespace App\Http\Controllers;

use App\Services\EmailVerificationService;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\UserCredential;

class EmailVerificationController extends Controller
{
    protected $verificationService;
    
    public function __construct(EmailVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }
    
    /**
     * Verify the user's email address.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request)
    {
        $userId = $request->route('id');
        $hash = $request->route('hash');
        
        // Check if the URL is valid
        if (!URL::hasValidSignature($request)) {
            return redirect(config('app.frontend_url') . '/email/verify/error?message=Invalid+or+expired+verification+link');
        }
        
        $verified = $this->verificationService->verifyEmail($userId, $hash);
        
        if ($verified) {
            return redirect(config('app.frontend_url') . '/email/verify/success');
        }
        
        return redirect(config('app.frontend_url') . '/email/verify/error');
    }
    
    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request)
    {
        $user = AuthService::Auth();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        if ($user->email_verified) {
            return response()->json(['message' => 'Email already verified'], 422);
        }
        
        $this->verificationService->resend($user);
        
        return response()->json(['message' => 'Verification link sent']);
    }
}