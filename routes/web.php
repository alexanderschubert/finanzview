<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BankConnectionController;
use App\Http\Controllers\Auth\OidcController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryRuleController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionImportController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\DashboardSettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Settings\DataExportController;
use App\Http\Controllers\CreditCardController;
use App\Http\Controllers\ReportController;


/*
|--------------------------------------------------------------------------
| Öffentliche Routen
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});


/*
 * Single Sign-On über OpenID Connect
 */

Route::middleware(['guest', 'throttle:20,1'])->group(function () {
    Route::get('/auth/oidc/redirect', [
        OidcController::class,
        'redirect',
    ])->name('oidc.redirect');

    Route::get('/auth/oidc/callback', [
        OidcController::class,
        'callback',
    ])->name('oidc.callback');
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

        Route::delete('/users/{user}/two-factor', [
            AdminController::class,
            'resetTwoFactor',
        ])->name('users.two-factor.destroy');

        Route::delete('/users/{user}/passkeys', [
            AdminController::class,
            'resetPasskeys',
        ])->name('users.passkeys.destroy');

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

Route::get('/credit-cards', [
    CreditCardController::class,
    'index',
])->name('credit-cards.index');

Route::get('/credit-cards/create', [
    CreditCardController::class,
    'create',
])->name('credit-cards.create');

Route::post('/credit-cards', [
    CreditCardController::class,
    'store',
])->name('credit-cards.store');

Route::get('/credit-cards/{creditCard}', [
    CreditCardController::class,
    'show',
])->name('credit-cards.show');

Route::get('/credit-cards/{creditCard}/edit', [
    CreditCardController::class,
    'edit',
])->name('credit-cards.edit');

Route::put('/credit-cards/{creditCard}', [
    CreditCardController::class,
    'update',
])->name('credit-cards.update');

Route::delete('/credit-cards/{creditCard}', [
    CreditCardController::class,
    'destroy',
])->name('credit-cards.destroy');
    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ])->name('dashboard');


    Route::get('/reports', [
        ReportController::class,
        'index',
    ])->name('reports.index');

    Route::get('/reports/export', [
        ReportController::class,
        'export',
    ])->name('reports.export');


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

    // Passwort bestätigen und zurück zu den Passkeys (für Hinzufügen/Löschen).
    Route::get('/settings/security/passkeys', fn () => redirect()->to(route('settings.security') . '#passkeys'))
        ->middleware('password.confirm')
        ->name('settings.security.passkeys');


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

    Route::resource('accounts', AccountController::class)->except('show');


    /*
     * =========================================================
     * BANKVERBINDUNGEN (FinTS)
     * =========================================================
     */

    Route::controller(BankConnectionController::class)->prefix('bank-connections')->name('bank-connections.')->group(function () {
        // Freigabe zuerst, damit „challenge“ nicht als Verbindungs-ID gilt.
        Route::get('/challenge', 'challenge')->name('challenge');
        Route::post('/challenge', 'confirm')->middleware('throttle:30,1')->name('confirm');
        Route::delete('/challenge', 'cancel')->name('cancel');

        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{bankConnection}/edit', 'edit')->name('edit');
        Route::put('/{bankConnection}', 'update')->name('update');
        Route::delete('/{bankConnection}', 'destroy')->name('destroy');

        Route::get('/{bankConnection}/setup', 'setup')->name('setup');
        Route::post('/{bankConnection}/tan-modes', 'tanModes')->middleware('throttle:10,1')->name('tan-modes');
        Route::post('/{bankConnection}/tan-mode', 'selectTanMode')->middleware('throttle:10,1')->name('tan-mode');
        Route::get('/{bankConnection}/accounts', 'accounts')->name('accounts');
        Route::post('/{bankConnection}/accounts', 'saveAccount')->name('accounts.store');
        Route::post('/links/{link}/reconcile', 'reconcile')->name('reconcile');

        Route::get('/{bankConnection}/sync', 'syncForm')->name('sync');
        Route::post('/{bankConnection}/sync', 'sync')->middleware('throttle:10,1')->name('sync.start');
    });


    /*
     * =========================================================
     * BUCHUNGEN
     * =========================================================
     */

    Route::get('/transactions/import', [TransactionImportController::class, 'create'])
        ->name('transactions.import.create');

    Route::post('/transactions/import', [TransactionImportController::class, 'upload'])
        ->name('transactions.import.upload');

    Route::get('/transactions/import/{token}', [TransactionImportController::class, 'preview'])
        ->name('transactions.import.preview');

    Route::post('/transactions/import/{token}', [TransactionImportController::class, 'store'])
        ->name('transactions.import.store');

    Route::delete('/transactions/import/{token}', [TransactionImportController::class, 'destroy'])
        ->name('transactions.import.destroy');

    Route::resource('transactions', TransactionController::class)->except('show');


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

    Route::resource('categories', CategoryController::class)->except('show');

    Route::post('/category-rules/apply', [CategoryRuleController::class, 'apply'])
        ->name('category-rules.apply');

    Route::resource('category-rules', CategoryRuleController::class)->except(['show', 'create']);

    Route::resource('tags', TagController::class)->only(['index', 'edit', 'update', 'destroy']);


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
