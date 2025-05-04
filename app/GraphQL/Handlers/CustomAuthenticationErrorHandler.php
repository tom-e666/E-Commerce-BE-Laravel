<?php

namespace App\GraphQL\Handlers;

use GraphQL\Error\Error;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use Nuwave\Lighthouse\Execution\ErrorHandler;

class CustomAuthenticationErrorHandler implements ErrorHandler
{
    public function __invoke(?Error $error, \Closure $next): ?array
    {
        if ($error && $error->getPrevious() instanceof AuthenticationException) {
            return [
                'message' => 'Authentication required.',
                'code' => 401,
            ];
        }

        return $next($error);
    }
}