<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ])->name('dashboard');

    Route::resource('accounts', AccountController::class);

});