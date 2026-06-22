<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind a null default so activeShop() never throws
        // before SetActiveShop middleware runs (e.g. in queue workers)
        $this->app->bind('active_shop', fn() => null);
    }

    public function boot(): void
    {
        // Load global helpers
        require_once app_path('helpers.php');
    }
}
