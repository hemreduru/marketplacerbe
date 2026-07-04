<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Web\AdminPlanController;
use App\Http\Controllers\Web\AdReportController;
use App\Http\Controllers\Web\AnalyticsReportController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BuyboxController;
use App\Http\Controllers\Web\ClaimController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\FinancialController;
use App\Http\Controllers\Web\HepsiburadaWebhookController;
use App\Http\Controllers\Web\MailWebhookController;
use App\Http\Controllers\Web\MarketplaceComparisonController;
use App\Http\Controllers\Web\MarketplaceSettingsController;
use App\Http\Controllers\Web\MasterProductController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\OrderReportController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ProfitReportController;
use App\Http\Controllers\Web\QuestionController;
use App\Http\Controllers\Web\RepricerController;
use App\Http\Controllers\Web\ReturnReportController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\StockReportController;
use App\Http\Controllers\Web\SubscriptionController;
use App\Http\Controllers\Web\TwoFactorController;
use App\Http\Controllers\Web\VatReportController;
use App\Http\Controllers\Web\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhooks (public, marketplace'a özel endpoint'ler)
Route::post('/webhooks/trendyol/{credentialUuid}', WebhookController::class)->name('webhooks.trendyol');
Route::post('/webhooks/hepsiburada/{credentialUuid}', HepsiburadaWebhookController::class)->name('webhooks.hepsiburada');
Route::post('/webhooks/ses/bounce', [MailWebhookController::class, 'bounce'])->name('webhooks.ses.bounce');
Route::post('/webhooks/ses/complaint', [MailWebhookController::class, 'complaint'])->name('webhooks.ses.complaint');

// Redirect root to dashboard or login
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    Route::get('/two-factor-challenge', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verifyChallenge'])->name('two-factor.verify');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes (require authentication)
Route::middleware('auth')->group(function () {
    // Subscription
    Route::get('/subscription/select', [SubscriptionController::class, 'select'])->name('subscription.select');
    Route::post('/subscription/trial', [SubscriptionController::class, 'startTrial'])->name('subscription.trial');
    Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');

    // Admin
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/plans', [AdminPlanController::class, 'index'])->name('admin.plans.index');
        Route::get('/plans/{plan}/edit', [AdminPlanController::class, 'edit'])->name('admin.plans.edit');
        Route::put('/plans/{plan}', [AdminPlanController::class, 'update'])->name('admin.plans.update');
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('settings.password');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile');
    Route::post('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');
    Route::post('/settings/language', [SettingsController::class, 'updateLanguage'])->name('settings.language');

    // Two-Factor (TOTP)
    Route::get('/settings/two-factor', [TwoFactorController::class, 'showSetup'])->name('two-factor.setup');
    Route::post('/settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/settings/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/data', [ProductController::class, 'getData'])->name('products.data');
    Route::post('/products/sync', [ProductController::class, 'sync'])->name('products.sync');
    Route::post('/products/update-price-stock', [ProductController::class, 'updatePriceStock'])->name('products.update-price-stock');

    // Master Products
    Route::get('/master-products/{id}', [MasterProductController::class, 'show'])->name('master-products.show');

    // Claims (returns)
    Route::middleware('feature:claims')->group(function () {
        Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/data', [ClaimController::class, 'getData'])->name('claims.data');
        Route::post('/claims/sync', [ClaimController::class, 'sync'])->name('claims.sync');
        Route::post('/claims/approve', [ClaimController::class, 'approve'])->name('claims.approve');
    });

    // Questions
    Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');
    Route::post('/questions/sync', [QuestionController::class, 'sync'])->name('questions.sync');
    Route::post('/questions/answer', [QuestionController::class, 'answer'])->name('questions.answer');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/data', [OrderController::class, 'getData'])->name('orders.data');
    Route::post('/orders/sync', [OrderController::class, 'sync'])->name('orders.sync');
    Route::post('/orders/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/label', [OrderController::class, 'getLabel'])->name('orders.label');

    // Marketplace Settings
    Route::get('/marketplace-settings', [MarketplaceSettingsController::class, 'index'])->name('marketplace.settings');
    Route::put('/marketplace-settings', [MarketplaceSettingsController::class, 'update'])->name('marketplace.settings.update');

    // Financial
    Route::middleware('feature:analytics')->group(function () {
        Route::get('/financial', [FinancialController::class, 'index'])->name('financial.index');
        Route::post('/financial/sync', [FinancialController::class, 'sync'])->name('financial.sync');
    });

    // Reports (Faz 4 — Analitik 2.0) — analytics feature ile korunur
    Route::middleware('feature:analytics')->group(function () {
        Route::get('/reports/sku-profit', [ProfitReportController::class, 'skuProfit'])->name('reports.sku-profit');
        Route::get('/reports/reconciliation', [ProfitReportController::class, 'reconciliation'])->name('reports.reconciliation');

        // PR 4.1 — Sipariş raporu + toplu işlemler
        Route::get('/reports/order', [OrderReportController::class, 'index'])->name('reports.order');
        Route::get('/reports/order/export', [OrderReportController::class, 'export'])->name('reports.order.export');
        Route::post('/reports/order/bulk', [OrderReportController::class, 'bulkAction'])->name('reports.order.bulk');

        // PR 4.2 — Stok raporu + satın alma listesi
        Route::get('/reports/stock', [StockReportController::class, 'index'])->name('reports.stock');
        Route::get('/reports/stock/purchase-order', [StockReportController::class, 'purchaseOrder'])->name('reports.stock.po');

        // PR 4.3 — İade analiz raporu
        Route::get('/reports/returns', [ReturnReportController::class, 'index'])->name('reports.returns');

        // PR 4.4 — Pazaryeri karşılaştırma pivotu
        Route::get('/reports/marketplace-comparison', [MarketplaceComparisonController::class, 'index'])->name('reports.marketplace-comparison');

        // PR 4.5 — KDV & vergi raporu + export
        Route::get('/reports/vat', [VatReportController::class, 'index'])->name('reports.vat');
        Route::get('/reports/vat/export', [VatReportController::class, 'export'])->name('reports.vat.export');

        // PR 4.6 — Reklam performans raporu
        Route::get('/reports/ads', [AdReportController::class, 'index'])->name('reports.ads');
        Route::post('/reports/ads/sync', [AdReportController::class, 'sync'])->name('reports.ads.sync');

        // PR 4.7 — Coğrafya + saat ısı haritası + cohort + LTM trend
        Route::get('/reports/analytics', [AnalyticsReportController::class, 'index'])->name('reports.analytics');

        // PR 4.8 — Rakip / buybox takip
        Route::get('/reports/buybox', [BuyboxController::class, 'index'])->name('reports.buybox');
        Route::post('/reports/buybox/sync', [BuyboxController::class, 'sync'])->name('reports.buybox.sync');

        // PR 4.9 — Kural tabanlı repricer
        Route::get('/repricer', [RepricerController::class, 'index'])->name('repricer.index');
        Route::post('/repricer', [RepricerController::class, 'store'])->name('repricer.store');
        Route::put('/repricer/{rule}', [RepricerController::class, 'update'])->name('repricer.update');
        Route::delete('/repricer/{rule}', [RepricerController::class, 'destroy'])->name('repricer.destroy');
        Route::post('/repricer/run', [RepricerController::class, 'run'])->name('repricer.run');
    });
});
