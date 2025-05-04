<?php

namespace App\GraphQL\Handlers;

use GraphQL\Error\Error;
use Nuwave\Lighthouse\Execution\AuthorizationErrorHandler;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;

class CustomAuthorizationErrorHandler extends AuthorizationErrorHandler
{
    public function __invoke(?Error $error, \Closure $next): ?array
    {
        if (
            $error && 
            $error->getPrevious() instanceof AuthorizationException
        ) {
            return [
                'message' => 'This action is unauthorized.',
                'code' => 403,
            ];
        }

        return $next($error);
    }
}