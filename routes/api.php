<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => __('api.success'),
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
            'locale' => app()->getLocale(),
        ]);
    });

    // Test language endpoint (for development)
    Route::get('/test-lang', function () {
        return response()->json([
            'success' => true,
            'message' => __('api.marketplace.list_success'),
            'locale' => app()->getLocale(),
            'translations' => [
                'success' => __('api.success'),
                'error' => __('api.error'),
                'marketplace_not_found' => __('api.marketplace.not_found'),
                'product_created' => __('api.product.create_success'),
            ],
        ]);
    });

    // Public routes (no auth required for now - will be protected in Phase 12)

    // Marketplace Management
    Route::prefix('marketplaces')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\MarketplaceController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\MarketplaceController::class, 'show']);
        Route::get('/{id}/stats', [App\Http\Controllers\Api\V1\MarketplaceController::class, 'stats']);
    });

    // Marketplace Credentials Management
    Route::prefix('marketplace-credentials')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\MarketplaceCredentialController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\V1\MarketplaceCredentialController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\MarketplaceCredentialController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\V1\MarketplaceCredentialController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\V1\MarketplaceCredentialController::class, 'destroy']);
        Route::post('/{id}/test', [App\Http\Controllers\Api\V1\MarketplaceCredentialController::class, 'test']);
    });

    // Product Management
    Route::prefix('products')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\ProductController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\V1\ProductController::class, 'store']);
        Route::post('/bulk', [App\Http\Controllers\Api\V1\ProductController::class, 'bulkStore']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\ProductController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\V1\ProductController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\V1\ProductController::class, 'destroy']);
        Route::post('/{id}/restore', [App\Http\Controllers\Api\V1\ProductController::class, 'restore']);
    });

    // Marketplace Product Operations (Sync)
    Route::prefix('marketplace-products')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\MarketplaceProductController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\MarketplaceProductController::class, 'show']);
        Route::post('/push', [App\Http\Controllers\Api\V1\MarketplaceProductController::class, 'push']);
        Route::post('/pull', [App\Http\Controllers\Api\V1\MarketplaceProductController::class, 'pull']);
        Route::post('/bulk-push', [App\Http\Controllers\Api\V1\MarketplaceProductController::class, 'bulkPush']);
        Route::post('/bulk-sync', [App\Http\Controllers\Api\V1\MarketplaceProductController::class, 'bulkSync']);
        Route::post('/{id}/sync', [App\Http\Controllers\Api\V1\MarketplaceProductController::class, 'sync']);
    });

    // Marketplace Order Management
    Route::prefix('marketplace-orders')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\MarketplaceOrderController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\MarketplaceOrderController::class, 'show']);
        Route::post('/fetch', [App\Http\Controllers\Api\V1\MarketplaceOrderController::class, 'fetch']);
        Route::put('/{id}/status', [App\Http\Controllers\Api\V1\MarketplaceOrderController::class, 'updateStatus']);
        Route::put('/{id}/tracking', [App\Http\Controllers\Api\V1\MarketplaceOrderController::class, 'updateTracking']);
        Route::post('/{id}/invoice', [App\Http\Controllers\Api\V1\MarketplaceOrderController::class, 'sendInvoice']);
    });
});
