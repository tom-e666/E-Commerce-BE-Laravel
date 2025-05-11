<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Models\UserCredential;
use App\Services\AuthService;

final readonly class UserCredentialResolver{

    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }

    public function updateUserInfo($_, array $args): array
    {
        $validation = validator($args, [
            'full_name' => 'string|max:255',
            'email' => 'email|max:255',
            'phone' => 'string|max:15',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors()->first(), 400);
        }
        $updateData = [];
        if (isset($args['full_name'])) {
            $updateData['full_name'] = $args['full_name'];
        }
        if (isset($args['email'])) {
            $updateData['email'] = $args['email'];
        }
        if (isset($args['phone'])) {
            $updateData['phone'] = $args['phone'];
        }
        if (isset($args['username'])) {
            $updateData['username'] = $args['username'];
        }

        if (empty($updateData)) {
            return $this->error('No fields to update', 400);
        }
        $user = AuthService::Auth();
        if(!$user){
            return $this->error('Unauthorized', 401);
        }

        $userCredential = UserCredential::where('id', $user->id)->first();
        if($userCredential){
            $userCredential->update($updateData);
        }

        return $this->success([
            'user' => $userCredential,
        ], 'success', 200);
    }

    public function changePassword($_, array $args): array
    {
        $validation = validator($args, [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors()->first(), 400);
        }

        $user = AuthService::Auth();
        if(!$user){
            return $this->error('Unauthorized', 401);
        }

        $userCredential = UserCredential::where('id', $user->id)->first();
        if($userCredential && password_verify($args['old_password'], $userCredential->password)){
            $userCredential->update([
                'password' => bcrypt($args['new_password']),
            ]);
            return $this->success([
                'message' => 'Password updated successfully',
            ], 'success', 200);
        } else {
            return $this->error('Old password is incorrect', 400);
        }
    }

    public function deleteUser($_, array $args): array
    {
        $user = AuthService::Auth();
        if(!$user){
            return $this->error('Unauthorized', 401);
        }

        $userCredential = UserCredential::where('id', $user->id)->first();
        if($userCredential){
            $userCredential->delete();
            return $this->success([
                'message' => 'User deleted successfully',
            ], 'success', 200);
        } else {
            return $this->error('User not found', 404);
        }
    }

    public function changeUserRole($_, array $args): array
    {
        $validation = validator($args, [
            'user_id' => 'required|exists:user_credentials,id',
            'role' => 'required|string|in:admin,staff,user',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors()->first(), 400);
        }

        $user = AuthService::Auth();
        if(!$user){
            return $this->error('Unauthorized', 401);
        } else if(!AuthService::isAdmin()){
            return $this->error('Forbidden', 403);
        }

        $userCredential = UserCredential::where('id', $args['user_id'])->first();
        if($userCredential){
            $userCredential->update([
                'role' => $args['role'],
            ]);
            return $this->success([
                'message' => 'User role updated successfully',
                'user' => $userCredential,
            ], 'success', 200);
        } else {
            return $this->error('User not found', 404);
        }
    }
}