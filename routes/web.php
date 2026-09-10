<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\DashboardSettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Settings\DataExportController;
use App\Http\Controllers\CreditCardController;


/*
|--------------------------------------------------------------------------
| Öffentliche Routen
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});


/*
|--------------------------------------------------------------------------
| Authentifizierte Routen
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {

    /*
     * =========================================================
     * ADMINISTRATION
     * =========================================================
     */

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [
            AdminController::class,
            'index',
        ])->name('index');

        Route::patch('/settings/registration', [
            AdminController::class,
            'toggleRegistration',
        ])->name('settings.registration');



        Route::get('/providers', [
            AdminController::class,
            'providers',
        ])->name('providers.index');


        Route::get('/providers/create', [
            AdminController::class,
            'createProvider',
        ])->name('providers.create');

        Route::post('/providers', [
            AdminController::class,
            'storeProvider',
        ])->name('providers.store');

        Route::get('/providers/{provider}/edit', [
            AdminController::class,
            'editProvider',
        ])->name('providers.edit');

        Route::patch('/providers/{provider}', [
            AdminController::class,
            'updateProvider',
        ])->name('providers.update');

        Route::patch('/providers/{provider}/toggle-active', [
            AdminController::class,
            'toggleProviderActive',
        ])->name('providers.toggle-active');

        Route::delete('/providers/{provider}', [
            AdminController::class,
            'destroyProvider',
        ])->name('providers.destroy');

        Route::patch('/users/{user}/toggle-active', [
            AdminController::class,
            'toggleActive',
        ])->name('users.toggle-active');

        Route::get('/users/{user}/edit', [
            AdminController::class,
            'edit',
        ])->name('users.edit');

        Route::patch('/users/{user}', [
            AdminController::class,
            'update',
        ])->name('users.update');

        Route::patch('/users/{user}/toggle-admin', [
            AdminController::class,
            'toggleAdmin',
        ])->name('users.toggle-admin');

        Route::delete('/users/{user}', [
            AdminController::class,
            'destroy',
        ])->name('users.destroy');
    });


    /*
     * =========================================================
     * DASHBOARD
     * =========================================================
     */

Route::get('/credit-cards/create', [
    CreditCardController::class,
    'create',
])->name('credit-cards.create');

Route::post('/credit-cards', [
    CreditCardController::class,
    'store',
])->name('credit-cards.store');

Route::get('/credit-cards', [CreditCardController::class, 'index'])->name('credit-cards.index');
    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ])->name('dashboard');


      Route::get('/settings/data-export', [
          DataExportController::class,
          'index',
      ])->name('settings.data-export');

      Route::post('/settings/data-export/transactions', [
          DataExportController::class,
          'transactionsCsv',
      ])->name('settings.data-export.transactions');

      Route::post('/settings/data-export/json', [
          DataExportController::class,
          'json',
      ])->name('settings.data-export.json');


      Route::post('/settings/data-export/import', [
          DataExportController::class,
          'importPreview',
      ])->name('settings.data-export.import');

      Route::post('/settings/data-export/import/restore', [
          DataExportController::class,
          'importRestore',
      ])->name('settings.data-export.import.restore');


Route::get('/settings/dashboard', [
        DashboardSettingsController::class,
        'edit',
    ])->name('settings.dashboard');

    Route::put('/settings/dashboard', [
        DashboardSettingsController::class,
        'update',
    ])->name('settings.dashboard.update');


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
     * FINANZEN
     * =========================================================
     */

    Route::get('/settings/financial', [
        SettingsController::class,
        'financial',
    ])->name('settings.financial');

    Route::put('/settings/financial', [
        SettingsController::class,
        'updateFinancial',
    ])->name('settings.financial.update');


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
     * WIEDERKEHRENDE BUCHUNGEN
     * ========================================================= */

    Route::resource(
        'recurring-transactions',
        RecurringTransactionController::class
    );


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
     * =========================================================
     * SONSTIGE KREDITFUNKTIONEN
     * =========================================================
     */

    Route::post('/loans/{loan}/extra-payment', [
        LoanController::class,
        'storeExtraPayment',
    ])->name('loans.extra-payment');

});
