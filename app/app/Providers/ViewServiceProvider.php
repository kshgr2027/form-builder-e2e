<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\NavigationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Use wildcard '*' to make navigation available to ALL views
        View::composer('*', function ($view) {
            // Only load navigation for authenticated users
            if (!auth()->check()) {
                $view->with('navigation', []);
                return;
            }

                $view->with('navigation', []);
        });
    }
}