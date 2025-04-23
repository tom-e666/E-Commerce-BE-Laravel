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

// Product routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/brands', [ProductController::class, 'getBrands']);

// Cart routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::post('/cart/apply-promotion', [CartController::class, 'applyPromotion']);
});

// Order routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});

// Admin order routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/orders', [AdminOrderController::class, 'index']);
    Route::get('/admin/orders/{id}', [AdminOrderController::class, 'show']);
    Route::put('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
});

// Review routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});

// Admin report routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/reports/revenue', [AdminReportController::class, 'revenueByTime']);
    Route::get('/admin/reports/best-sellers', [AdminReportController::class, 'bestSellingProducts']);
    Route::get('/admin/reports/reviews', [AdminReportController::class, 'reviewManagement']);
    Route::delete('/admin/reports/reviews/{id}', [AdminReportController::class, 'deleteReview']);
    Route::get('/admin/reports/revenue-by-brand', [AdminReportController::class, 'revenueByBrand']);
    Route::get('/admin/reports/conversion-rate', [AdminReportController::class, 'orderConversionRate']);
    Route::get('/admin/reports/new-customers', [AdminReportController::class, 'newCustomersByTime']);
    Route::get('/admin/reports/return-rate', [AdminReportController::class, 'returnRate']);
    Route::get('/admin/reports/revenue-by-category', [AdminReportController::class, 'revenueByCategory']);
    Route::get('/admin/reports/returning-customers', [AdminReportController::class, 'returningCustomers']);
    Route::get('/admin/reports/average-delivery-time', [AdminReportController::class, 'averageDeliveryTime']);
    Route::get('/admin/reports/promotion-usage', [AdminReportController::class, 'promotionUsage']);
});

// Promotion routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/promotions', [PromotionController::class, 'index']);
    Route::post('/promotions', [PromotionController::class, 'store']);
    Route::get('/promotions/{id}', [PromotionController::class, 'show']);
    Route::put('/promotions/{id}', [PromotionController::class, 'update']);
    Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);
    Route::post('/promotions/validate', [PromotionController::class, 'validatePromoCode']);
});

// Shipping routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/shippings', [ShippingController::class, 'index']);
    Route::post('/shippings', [ShippingController::class, 'store']);
    Route::get('/shippings/{id}', [ShippingController::class, 'show']);
    Route::put('/shippings/{id}', [ShippingController::class, 'update']);
    Route::delete('/shippings/{id}', [ShippingController::class, 'destroy']);
    Route::post('/shippings/calculate-fee', [ShippingController::class, 'calculateShippingFee']);
});
