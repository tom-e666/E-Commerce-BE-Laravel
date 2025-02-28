<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\User;

final readonly class UserQuery
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        $query = User::query();
        if (isset($args['email'])) {
            $query->where('email', 'LIKE', $args['email']);
            return $query->get();
        }
        return User::all();
    }
}
