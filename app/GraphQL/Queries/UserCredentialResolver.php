<?php declare(strict_types=1);

namespace App\GraphQL\Queries;
use App\Models\UserCredential;
use App\GraphQL\Traits\GraphQLResponse;
use App\Services\AuthService;

final readonly class UserCredentialResolver{

    use GraphQLResponse;

    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }

    public function getUserCredential($_, array $args): array
    {
        $user = AuthService::Auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        
        try {
            $userCredential = UserCredential::where('id', $user->id)->first();
    
            if (!$userCredential) {
                return $this->error('User not found', 404);
            }
    
            return $this->success([
                'user' => $userCredential,
            ], 'success', 200);
    
        } catch (\Exception $e) {
            return $this->error('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}