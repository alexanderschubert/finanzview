<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\BudgetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {

    /*
     * =========================================================
     * DASHBOARD
     * =========================================================
     */

    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ])->name('dashboard');


    /*
     * =========================================================
     * EINSTELLUNGEN
     * =========================================================
     */

    Route::get('/settings', [
        SettingsController::class,
        'index',
    ])->name('settings.index');


    /*
     * =========================================================
     * PROFIL
     * =========================================================
     */

    Route::get('/settings/profile', [
        SettingsController::class,
        'profile',
    ])->name('settings.profile');

    Route::put('/settings/profile', [
        SettingsController::class,
        'updateProfile',
    ])->name('settings.profile.update');


    /*
     * =========================================================
     * SICHERHEIT
     * =========================================================
     */

    Route::get('/settings/security', [
        SettingsController::class,
        'security',
    ])->name('settings.security');

    Route::put('/settings/security', [
        SettingsController::class,
        'updatePassword',
    ])->name('settings.security.password');


    /*
     * =========================================================
     * DARSTELLUNG
     * =========================================================
     */

    Route::get('/settings/appearance', [
        SettingsController::class,
        'appearance',
    ])->name('settings.appearance');

    Route::put('/settings/appearance', [
        SettingsController::class,
        'updateAppearance',
    ])->name('settings.appearance.update');


    /*
     * =========================================================
     * KONTEN
     * =========================================================
     */

    Route::resource('accounts', AccountController::class);


    /*
     * =========================================================
     * BUCHUNGEN
     * =========================================================
     */

    Route::resource('transactions', TransactionController::class);


    /*
     * =========================================================
     * KATEGORIEN
     * =========================================================
     */

    Route::resource('categories', CategoryController::class);


    /*
     * =========================================================
     * BUDGETS
     * =========================================================
     */

    Route::resource('budgets', BudgetController::class);


    /*
     * =========================================================
     * KREDITE
     * =========================================================
     */

    Route::resource('loans', LoanController::class);

    /*
     * Sondertilgung
     */
    Route::post('/loans/{loan}/extra-payment', [
        LoanController::class,
        'storeExtraPayment',
    ])->name('loans.extra-payment');

});