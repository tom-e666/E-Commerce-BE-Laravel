<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GHNService;
use App\Services\ZalopayService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GHNService::class, function ($app) {
            return new GHNService();
        });
        $this->app->singleton(ZalopayService::class, function ($app) {
            return new ZalopayService();
        });
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
