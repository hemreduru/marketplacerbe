<?php

use App\Http\Controllers\Web\AdminPlanController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ClaimController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\FinancialController;
use App\Http\Controllers\Web\MarketplaceSettingsController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\QuestionController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\SubscriptionController;
use Illuminate\Support\Facades\Route;

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
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes (require authentication)
Route::middleware('auth')->group(function () {
    // Subscription
    Route::get('/subscription/select', [SubscriptionController::class, 'select'])->name('subscription.select');
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

    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/data', [ProductController::class, 'getData'])->name('products.data');
    Route::post('/products/sync', [ProductController::class, 'sync'])->name('products.sync');
    Route::post('/products/update-price-stock', [ProductController::class, 'updatePriceStock'])->name('products.update-price-stock');

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
});
