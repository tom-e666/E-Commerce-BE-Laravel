<?php

namespace App\GraphQL\Enums;

class ShippingStatus
{
    public const PENDING = 'pending';
    public const DELIVERING = 'delivering';
    public const DELIVERED = 'delivered';
    public const FAILED = 'failed';

    public static function getAllStatuses(): array
    {
        return [
            self::PENDING,
            self::DELIVERING,
            self::DELIVERED,
            self::FAILED,
        ];
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::getAllStatuses());
    }
}
