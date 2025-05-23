<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VNPayIPN
{
    public function handle(Request $request, Closure $next)
    {
        // Cho phép VNPay gọi IPN từ bất kỳ domain nào
        if ($request->is('vnpay/ipn')) {
            $response = $next($request);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'POST');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
            return $response;
        }
        
        return $next($request);
    }
}