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
    // Health check (public)
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => __('api.success'),
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
            'locale' => app()->getLocale(),
        ]);
    });

    // Authentication routes (public)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [App\Http\Controllers\Api\V1\AuthController::class, 'register']);
        Route::post('/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login']);

        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
            Route::get('/me', [App\Http\Controllers\Api\V1\AuthController::class, 'me']);
            Route::post('/refresh', [App\Http\Controllers\Api\V1\AuthController::class, 'refresh']);
            Route::post('/revoke-all', [App\Http\Controllers\Api\V1\AuthController::class, 'revokeAll']);
        });
    });

    // Test language endpoint (for development - public)
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

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {

    // User Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\ProfileController::class, 'show']);
        Route::put('/', [App\Http\Controllers\Api\V1\ProfileController::class, 'updateProfile']);
        Route::put('/password', [App\Http\Controllers\Api\V1\ProfileController::class, 'updatePassword']);
    });

    // User Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\UserSettingController::class, 'show']);
        Route::put('/', [App\Http\Controllers\Api\V1\UserSettingController::class, 'update']);
        Route::put('/theme', [App\Http\Controllers\Api\V1\UserSettingController::class, 'updateTheme']);
        Route::put('/language', [App\Http\Controllers\Api\V1\UserSettingController::class, 'updateLanguage']);
    });

    // Languages (public but inside auth group for consistency)
    Route::prefix('languages')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\LanguageController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\LanguageController::class, 'show']);
    });

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

    // Marketplace Claim (Returns) Management
    Route::prefix('marketplace-claims')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\MarketplaceClaimController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\MarketplaceClaimController::class, 'show']);
        Route::post('/fetch', [App\Http\Controllers\Api\V1\MarketplaceClaimController::class, 'fetch']);
        Route::post('/{id}/approve', [App\Http\Controllers\Api\V1\MarketplaceClaimController::class, 'approve']);
        Route::post('/{id}/reject', [App\Http\Controllers\Api\V1\MarketplaceClaimController::class, 'reject']);
    });

    // Marketplace Q&A Management
    Route::prefix('marketplace-questions')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\MarketplaceQuestionController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\MarketplaceQuestionController::class, 'show']);
        Route::post('/fetch', [App\Http\Controllers\Api\V1\MarketplaceQuestionController::class, 'fetch']);
        Route::post('/{id}/answer', [App\Http\Controllers\Api\V1\MarketplaceQuestionController::class, 'answer']);
    });

    // Marketplace Categories & Brands Cache
    Route::prefix('marketplace-categories')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\MarketplaceDataController::class, 'listCategories']);
        Route::get('/tree', [App\Http\Controllers\Api\V1\MarketplaceDataController::class, 'getCategoryTree']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\MarketplaceDataController::class, 'getCategory']);
    });

    Route::prefix('marketplace-brands')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\MarketplaceDataController::class, 'listBrands']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\MarketplaceDataController::class, 'getBrand']);
    });

    // Marketplace Financial Data (CHE API)
    Route::prefix('marketplace-financials')->group(function () {
        // Settlements (Sales transactions)
        Route::get('/settlements', [App\Http\Controllers\Api\V1\MarketplaceFinancialController::class, 'listSettlements']);
        Route::post('/settlements/fetch', [App\Http\Controllers\Api\V1\MarketplaceFinancialController::class, 'fetchSettlements']);

        // Other Financials (Deductions, fees, penalties)
        Route::get('/other-financials', [App\Http\Controllers\Api\V1\MarketplaceFinancialController::class, 'listOtherFinancials']);
        Route::post('/other-financials/fetch', [App\Http\Controllers\Api\V1\MarketplaceFinancialController::class, 'fetchOtherFinancials']);

        // Cargo Invoices
        Route::get('/cargo-invoices', [App\Http\Controllers\Api\V1\MarketplaceFinancialController::class, 'listCargoInvoices']);
        Route::post('/cargo-invoices/fetch', [App\Http\Controllers\Api\V1\MarketplaceFinancialController::class, 'fetchCargoInvoice']);

        // Financial Summary & Dashboard
        Route::get('/summary', [App\Http\Controllers\Api\V1\MarketplaceFinancialController::class, 'getSummary']);
    });

    // Profit Calculation & Additional Expenses
    Route::prefix('profit')->group(function () {
        // Profit calculation
        Route::post('/calculate', [App\Http\Controllers\Api\V1\ProfitController::class, 'calculate']);
        Route::post('/bulk-calculate', [App\Http\Controllers\Api\V1\ProfitController::class, 'bulkCalculate']);
        Route::get('/summary', [App\Http\Controllers\Api\V1\ProfitController::class, 'summary']);

        // Additional expenses management
        Route::get('/expenses', [App\Http\Controllers\Api\V1\ProfitController::class, 'listExpenses']);
        Route::post('/expenses', [App\Http\Controllers\Api\V1\ProfitController::class, 'addExpense']);
        Route::put('/expenses/{id}', [App\Http\Controllers\Api\V1\ProfitController::class, 'updateExpense']);
        Route::delete('/expenses/{id}', [App\Http\Controllers\Api\V1\ProfitController::class, 'deleteExpense']);
    });

    }); // End of auth:sanctum middleware group
});
