<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BudgetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ])->name('dashboard');
    
    Route::get('/settings', [
        SettingsController::class,
        'index'
    ])->name('settings.index');

    Route::resource('accounts', AccountController::class);

    Route::resource('transactions', TransactionController::class);

    Route::resource('categories', CategoryController::class);
    
    Route::resource('budgets', BudgetController::class);

});
