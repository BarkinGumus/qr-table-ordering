<?php

namespace App\Providers;

use App\Tenancy\Tenant;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One Tenant per request or queued job; never shared between them.
        $this->app->scoped(Tenant::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
