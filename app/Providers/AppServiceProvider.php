<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GHNService;
use App\Services\ZalopayService;
use App\Services\EmailVefificationService;
use Illuminate\Support\Facades\Config;
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
        $this->app->singleton(EmailVerificationService::class, function ($app) {
            return new EmailVerificationService();
        });
        Config::set('app.frontend_url', env('FRONTEND_URL', env('APP_URL')));
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
