<?php

namespace App\GraphQL\Directives;

use App\Services\AuthService;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class HasRoleDirective extends BaseDirective implements FieldMiddleware
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Limit field access to users with specific roles.
"""
directive @hasRole(
  """
  The roles required to access the field.
  """
  roles: [String!]!
) on FIELD_DEFINITION
GRAPHQL;
    }

    /**
     * Handle field middleware.
     *
     * @param \Nuwave\Lighthouse\Schema\Values\FieldValue $fieldValue
     * @return void
     */
    public function handleField(FieldValue $fieldValue): void
    {
        $originalResolver = $fieldValue->getResolver();

        $fieldValue->setResolver(function ($root, array $args, GraphQLContext $context, ResolveInfo $info) use ($originalResolver) {
            $roles = $this->directiveArgValue('roles', ['admin']);
            
            $user = AuthService::Auth();
            
            if (!$user) {
                return [
                    'code' => 401,
                    'message' => 'Authentication required.'
                ];
            }
            $hasRole = in_array($user->role, $roles);

            if (!$hasRole) {
                return [
                    'code' => 403,
                    'message' => 'Unauthorized. You do not have the required permissions.'
                ];
            }

            return $originalResolver($root, $args, $context, $info);
        });
    }
}