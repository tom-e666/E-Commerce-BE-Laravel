<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\UserCredential;
use App\Services\AuthService;
use App\GraphQL\Traits\GraphQLResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final readonly class UserCredentialResolver{
    use GraphQLResponse;
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }
    public function getUserCredential($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        return $this->success([
            'user' => $user,
        ], 'User retrieved successfully', 200);
    }
    public function getAllUsers($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        // Check if user can view all users
        if (Gate::denies('viewAny', UserCredential::class)) {
            return $this->error('You are not authorized to view all users', 403);
        }
        
        $query = UserCredential::query();
        
        // Apply filters
        if (isset($args['role'])) {
            $query->where('role', $args['role']);
        }
        
        if (isset($args['search'])) {
            $search = $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%$search%")
                  ->orWhere('full_name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }
        
        // Get all users without pagination since the schema doesn't define pagination params
        $users = $query->get();
        
        return $this->success([
            'users' => $users,
        ], 'Users retrieved successfully', 200);
    }
    public function getUser($_, array $args)
    {
        $currentUser = AuthService::Auth();
        if (!$currentUser) {
            return $this->error('Unauthorized', 401);
        }
        
        $validator = Validator::make($args, [
            'user_id' => 'required|exists:user_credentials,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 400);
        }
        
        $targetUser = UserCredential::find($args['user_id']);
        
        // Check if user can view this user
        if (Gate::denies('view', $targetUser)) {
            return $this->error('You are not authorized to view this user', 403);
        }
        
        return $this->success([
            'user' => $targetUser,
        ], 'User retrieved successfully', 200);
    }
    public function getCurrentUser($_, array $args)
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        return $this->success([
            'user' => $user,
        ], 'User retrieved successfully', 200);
    }
}
