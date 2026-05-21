<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CashierDeviceController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Developer\PosInstanceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::redirect('/developer', '/developer/pos');

Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');
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

Route::get('/developer/pos', [PosInstanceController::class, 'index'])->name('developer.pos.index');
Route::post('/developer/pos', [PosInstanceController::class, 'store'])->name('developer.pos.store');
Route::put('/developer/pos/{posInstance}/status', [PosInstanceController::class, 'updateStatus'])->name('developer.pos.status');
