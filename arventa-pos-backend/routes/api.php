<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PairingController;
use App\Http\Controllers\Api\StorefrontController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/pairing/connect', [PairingController::class, 'connect']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/sync', [StorefrontController::class, 'sync']);
    Route::post('/transactions', [TransactionController::class, 'store']);
});
