<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\UserCredential;
use App\Services\AuthService;
use App\GraphQL\Traits\GraphQLResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
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
            // AuthService::Auth() now returns null on failure and logs the specific JWT error.
            // The log will give you the precise reason (e.g., "Token expired", "Token invalid", "Token not provided").
            return $this->error('Unauthorized: Could not authenticate user from token.', 401);
        }
        return $this->success([
            'user' => $user,
        ], 'User retrieved successfully', 200);
    }
}
