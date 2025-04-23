<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ShippingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');