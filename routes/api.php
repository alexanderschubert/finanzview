<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankConnectionController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\PlanningController;
use App\Http\Controllers\Api\ReportController;
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
    Route::patch('/accounts/{id}', [ReadController::class, 'updateAccount'])->whereNumber('id');
    Route::get('/categories', [ReadController::class, 'categories']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show'])->whereNumber('id');
    Route::patch('/transactions/{id}', [TransactionController::class, 'update'])->whereNumber('id');

    Route::get('/plan', [PlanningController::class, 'plan']);
    Route::post('/plan', [PlanningController::class, 'store']);
    Route::post('/plan/groups', [PlanningController::class, 'storeGroup']);
    Route::patch('/plan/groups/{id}', [PlanningController::class, 'updateGroup'])->whereNumber('id');
    Route::delete('/plan/groups/{id}', [PlanningController::class, 'destroyGroup'])->whereNumber('id');
    Route::post('/plan/groups/{id}/items', [PlanningController::class, 'storeItem'])->whereNumber('id');
    Route::patch('/plan/items/{id}', [PlanningController::class, 'updateItem'])->whereNumber('id');
    Route::delete('/plan/items/{id}', [PlanningController::class, 'destroyItem'])->whereNumber('id');

    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/{id}', [LoanController::class, 'show'])->whereNumber('id');
    Route::patch('/loans/{id}', [LoanController::class, 'update'])->whereNumber('id');

    Route::get('/reports/month', [ReportController::class, 'month']);

    Route::get('/bank-connections', [BankConnectionController::class, 'index']);
    Route::post('/bank-connections/challenge', [BankConnectionController::class, 'confirm'])->middleware('throttle:30,1');
    Route::delete('/bank-connections/challenge', [BankConnectionController::class, 'cancel']);
    Route::post('/bank-connections/{id}/sync', [BankConnectionController::class, 'sync'])->middleware('throttle:10,1')->whereNumber('id');
});
