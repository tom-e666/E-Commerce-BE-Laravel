<?php

namespace App\GraphQL\Enums;

class PaymentStatus
{
    public const PENDING = 'pending';
    public const COMPLETED = 'completed';
    public const COD = 'cod';
    public const FAILED = 'failed';
    public const REFUNDED = 'refunded';

    public static function getAllStatuses(): array
    {
        return [
            self::PENDING,
            self::COMPLETED,
            self::COD,
            self::FAILED,
            self::REFUNDED,
        ];
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::getAllStatuses());
    }
}
