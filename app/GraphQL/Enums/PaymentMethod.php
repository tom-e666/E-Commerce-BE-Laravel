<?php

namespace App\GraphQL\Enums;

class PaymentMethod
{
    public const VNPAY = 'vnpay';
    public const ZALOPAY = 'zalopay';
    public const COD = 'cod';
    public const MOMO = 'momo';
    public const BANK_TRANSFER = 'bank_transfer';

    public static function getAllMethods(): array
    {
        return [
            self::VNPAY,
            self::ZALOPAY,
            self::COD,
            self::MOMO,
            self::BANK_TRANSFER,
        ];
    }

    public static function isValidMethod(string $method): bool
    {
        return in_array($method, self::getAllMethods());
    }
}
