<?php

namespace App\GraphQL\Traits;

trait GraphQLResponse
{
    public function success($data, string $message = 'Success', int $code = 200): array
    {
        return array_merge([
            'code' => $code,
            'message' => $message,
        ], $data ?? []);
    }

    public function error(string $message = 'Error', int $code = 400): array
    {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }
}
