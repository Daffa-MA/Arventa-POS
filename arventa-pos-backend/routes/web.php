<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CashierDeviceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Developer\AuthController as DeveloperAuthController;
use App\Http\Controllers\Developer\DeveloperPosController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::redirect('/developer', '/developer/pos');

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.store');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware('arventa.admin')->group(function (): void {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.alias');
    Route::get('/admin/settings', [DashboardController::class, 'settings'])->name('admin.settings');
    Route::get('/admin/products', [DashboardController::class, 'products'])->name('admin.products');
    Route::get('/admin/app-preview', [DashboardController::class, 'appPreview'])->name('admin.app-preview');
    Route::get('/admin/devices', [CashierDeviceController::class, 'index'])->name('admin.devices');
    Route::post('/admin/devices/pairing-codes', [CashierDeviceController::class, 'storePairing'])->name('admin.devices.pairing-codes.store');
    Route::delete('/admin/devices/pairing-codes/expired', [CashierDeviceController::class, 'destroyExpiredPairingCodes'])->name('admin.devices.pairing-codes.expired.destroy');
    Route::delete('/admin/devices/pairing-codes/{pairingCode}', [CashierDeviceController::class, 'destroyPairingCode'])->name('admin.devices.pairing-codes.destroy');
    Route::put('/admin/devices/{device}/revoke', [CashierDeviceController::class, 'revoke'])->name('admin.devices.revoke');
    Route::get('/admin/transactions', [DashboardController::class, 'transactions'])->name('admin.transactions');
    Route::put('/admin/settings', [DashboardController::class, 'updateSetting'])->name('admin.settings.update');
    Route::put('/admin/app-preview', [DashboardController::class, 'updateAppPreview'])->name('admin.app-preview.update');
    Route::post('/admin/products', [ProductController::class, 'store'])->name('admin.products.store');
    Route::put('/admin/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
});

Route::get('/developer/login', [DeveloperAuthController::class, 'showLogin'])->name('developer.login');
Route::post('/developer/login', [DeveloperAuthController::class, 'login'])->name('developer.login.store');
Route::post('/developer/logout', [DeveloperAuthController::class, 'logout'])->name('developer.logout');

Route::middleware('arventa.developer')->group(function (): void {
    Route::get('/developer/pos', [DeveloperPosController::class, 'index'])->name('developer.pos.index');
    Route::post('/developer/pos', [DeveloperPosController::class, 'store'])->name('developer.pos.store');
    Route::post('/developer/pos/{posInstance}/deploy', [DeveloperPosController::class, 'deploy'])->name('developer.pos.deploy');
    Route::patch('/developer/pos/{posInstance}/status', [DeveloperPosController::class, 'updateStatus'])->name('developer.pos.status');
    Route::delete('/developer/pos/{posInstance}', [DeveloperPosController::class, 'destroy'])->name('developer.pos.destroy');
});
