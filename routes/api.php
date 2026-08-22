<?php

use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Version 1 (V1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Products (Fast cached reads)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    // Orders & Checkout (Rate-limited to protect against bot floods)
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/track', [OrderController::class, 'track']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);

        // Payments (Razorpay)
        Route::post('/payments/razorpay/create-order', [PaymentController::class, 'createRazorpayOrder']);
        Route::post('/payments/razorpay/verify', [PaymentController::class, 'verifyRazorpayPayment']);
        Route::post('/payments/razorpay/fail', [PaymentController::class, 'failRazorpayPayment']);
        Route::post('/payments/razorpay/webhook', [PaymentController::class, 'handleWebhook']);

        // Shiprocket Webhook (renamed path to avoid restricted keyword 'shiprocket')
        Route::post('/shipment/webhook', [\App\Http\Controllers\Api\V1\ShiprocketWebhookController::class, 'handle']);

        // Reviews
        Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);
        Route::post('/reviews', [ReviewController::class, 'store']);
    });
});
