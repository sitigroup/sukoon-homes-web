<?php

namespace App\Plugins\Theme;

use App\Plugins\Theme\Services\ThemeService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/theme.php', 'sukoon-theme');
    }

    public function boot(): void
    {
        $base = __DIR__;

        $this->loadMigrationsFrom($base . '/database/migrations');
        $this->loadViewsFrom($base . '/views', 'theme');

        Route::middleware('api')
            ->prefix('api')
            ->group($base . '/routes/api.php');

        Route::middleware(['web', 'auth', 'checkLogin'])
            ->group($base . '/routes/web.php');

        if (Schema::hasTable('theme_settings')) {
            ThemeService::setting();
        }

        View::composer(['layouts.main', 'layouts.sidebar'], function ($view) {
            if (ThemeService::isPublished()) {
                $view->with('sukoonPublishedTheme', ThemeService::publishedPayload());
            }
        });
    }
}
