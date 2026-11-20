<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankConnectionController;
use App\Http\Controllers\Api\ReadController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JSON-API v1 (Präfix api/v1) für die native App
|--------------------------------------------------------------------------
*/

Route::post('/auth/token', [AuthController::class, 'token'])->middleware('throttle:10,1');

Route::middleware(['auth.api', 'throttle:120,1'])->group(function () {
    Route::delete('/auth/token', [AuthController::class, 'destroy']);

    Route::get('/me', [ReadController::class, 'me']);
    Route::get('/me/avatar', [ReadController::class, 'avatar']);
    Route::get('/dashboard', [ReadController::class, 'dashboard']);
    Route::get('/accounts', [ReadController::class, 'accounts']);
    Route::get('/categories', [ReadController::class, 'categories']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show'])->whereNumber('id');
    Route::patch('/transactions/{id}', [TransactionController::class, 'update'])->whereNumber('id');

    Route::get('/bank-connections', [BankConnectionController::class, 'index']);
    Route::post('/bank-connections/challenge', [BankConnectionController::class, 'confirm'])->middleware('throttle:30,1');
    Route::delete('/bank-connections/challenge', [BankConnectionController::class, 'cancel']);
    Route::post('/bank-connections/{id}/sync', [BankConnectionController::class, 'sync'])->middleware('throttle:10,1')->whereNumber('id');
});
