<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\MarketplaceSettingsController;
use App\Http\Controllers\Web\FinancialController;

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
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('settings.password');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile');
    Route::post('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');
    Route::post('/settings/language', [SettingsController::class, 'updateLanguage'])->name('settings.language');

    // Marketplace Settings
    Route::get('/marketplace-settings', [MarketplaceSettingsController::class, 'index'])->name('marketplace.settings');
    Route::put('/marketplace-settings', [MarketplaceSettingsController::class, 'update'])->name('marketplace.settings.update');

    // Financial
    Route::get('/financial', [FinancialController::class, 'index'])->name('financial.index');
    Route::post('/financial/sync', [FinancialController::class, 'sync'])->name('financial.sync');
});
