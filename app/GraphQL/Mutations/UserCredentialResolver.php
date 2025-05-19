<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Models\UserCredential;
use App\Services\AuthService;
use App\GraphQL\Traits\GraphQLResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

final readonly class UserCredentialResolver{

    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }

    public function updateUserInfo($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check if user can update their profile
        if (Gate::denies('update', $user)) {
            return $this->error('You are not authorized to update this profile', 403);
        }
        
        $validator = Validator::make($args, [
            'email' => 'email|unique:user_credentials,email,'.$user->id,
            'phone' => 'string|unique:user_credentials,phone,'.$user->id,
            'full_name' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        
        // Update only provided fields
        $updateData = [];
        if (isset($args['email'])) $updateData['email'] = $args['email'];
        if (isset($args['phone'])) $updateData['phone'] = $args['phone'];
        if (isset($args['full_name'])) $updateData['full_name'] = $args['full_name'];
        
        $user->update($updateData);
        
        return $this->success([
            'user' => $user,
        ], 'User information updated successfully', 200);
    }
    
    public function changePassword($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check if user can change their password
        if (Gate::denies('changePassword', $user)) {
            return $this->error('You are not authorized to change this password', 403);
        }
        
        $validator = Validator::make($args, [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8|different:old_password',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        
        // Verify old password
        if (!Hash::check($args['old_password'], $user->password)) {
            return $this->error('Current password is incorrect', 400);
        }
        
        $user->password = Hash::make($args['new_password']);
        $user->save();
        
        // Optional: Invalidate all user's tokens after password change
        // $user->tokens()->delete();
        
        return $this->success([], 'Password changed successfully', 200);
    }
    public function updateUserRole($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        if (Gate::denies('updateRole', $user)) {
            return $this->error('You are not authorized to update user roles', 403);
        }
        
        $validator = Validator::make($args, [
            'user_id' => 'required|exists:user_credentials,id',
            'role' => 'required|in:admin,staff,user',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        
        $targetUser = UserCredential::find($args['user_id']);
        if ($targetUser->isAdmin() && $args['role'] !== 'admin') {
            $adminCount = UserCredential::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return $this->error('Cannot remove the last administrator', 400);
            }
        }
        
        $targetUser->role = $args['role'];
        $targetUser->save();
        
        return $this->success([
            'user' => $targetUser,
        ], 'User role updated successfully', 200);
    }
    public function resendVerification($_, array $args)
{
    $user = AuthService::Auth();
    
    if (!$user) {
        return $this->error('Unauthorized', 401);
    }
    
    if ($user->email_verified) {
        return $this->error('Email already verified', 422);
    }
    
    $this->emailVerificationService->resend($user);
    
    return $this->success([], 'Verification link sent', 200);
}
    public function deleteUser($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        if (Gate::denies('delete', $user)) {
            return $this->error('You are not authorized to delete this user', 403);
        }
        
        $validator = Validator::make($args, [
            'user_id' => 'required|exists:user_credentials,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        
        $targetUser = UserCredential::find($args['user_id']);
        if ($targetUser->isAdmin()) {
            return $this->error('Cannot delete an admin user', 400);
        }
        
        $targetUser->delete();
        
        return $this->success([], 'User deleted successfully', 200);
    }
}