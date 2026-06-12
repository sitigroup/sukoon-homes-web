<?php

namespace App\Plugins\SeoEngine;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;

class SeoEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeoEngineSettingsService::class);
    }

    public function boot(): void
    {
        $base = app_path('Plugins/SeoEngine');

        $this->loadMigrationsFrom($base . '/database/migrations');
        $this->loadViewsFrom($base . '/resources/views', 'seo-engine');
        $this->loadTranslationsFrom($base . '/resources/lang', 'seo-engine');

        if (file_exists($base . '/config/seo-engine-permissions.php')) {
            $module = require $base . '/config/seo-engine-permissions.php';
            $existing = config('rolepermission.modules', []);
            config(['rolepermission.modules' => array_merge($existing, [$module])]);
        }

        Route::middleware('web')->group($base . '/routes/web.php');
        Route::prefix('api')->middleware('api')->group($base . '/routes/api.php');

        // Scheduled commands (seo-engine:generate-pages, etc.) registered in TASK B3.
    }
}
