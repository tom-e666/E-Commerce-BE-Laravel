<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\UserCredential;
use App\Services\AuthService;
use App\GraphQL\Traits\GraphQLResponse;
final readonly class UserCredentialResolver{

    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }

    public function getUserCredential($_, array $args)
{
    try {
        $user= auth('api')->user();
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
                'user' => null
            ];
        }
        return [
            'code' => 200,
            'message' => 'success',
            'user' => $user
        ];
    } catch (\Exception $e) {
        // Catch token exceptions gracefully
        return [
            'code' => 401,
            'message' => 'Authentication failed: ' . $e->getMessage(), // Fixed: now returns string
            'user' => null
        ];
    }
}
    
}