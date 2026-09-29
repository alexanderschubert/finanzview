<?php

namespace App\Providers;

use App\Services\Fints\FintsClient;
use App\Services\Fints\PhpFintsClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bankabruf per FinTS; in Tests durch eine simulierte Bank ersetzt.
        $this->app->bind(FintsClient::class, PhpFintsClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}