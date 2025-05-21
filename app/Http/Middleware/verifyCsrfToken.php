<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Payment webhook URLs
        'webhooks/zalopay/callback', // Exact path for ZaloPay
        'zalopay-callback',          // Alternative ZaloPay callback path
        'webhooks/ghn',              // GHN webhook
        // You can use wildcards, but be specific if possible
        // 'webhooks/*',
        // 'payment/*',
    ];
}