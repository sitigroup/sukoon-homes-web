<?php

namespace App\Plugins\SeoEngine;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Plugins\SeoEngine\Observers\ArticleSlugObserver;
use App\Plugins\SeoEngine\Observers\ProjectSlugObserver;
use App\Plugins\SeoEngine\Observers\PropertySlugObserver;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;

class SeoEngineServiceProvider extends ServiceProvider
{
    private array $slugObservers = [
        \App\Models\Property::class => PropertySlugObserver::class,
        \App\Models\Projects::class => ProjectSlugObserver::class,
        \App\Models\Article::class => ArticleSlugObserver::class,
    ];

    public function register(): void
    {
        $this->app->singleton(SeoEngineSettingsService::class);
        $this->app->singleton(SeoEngineRedirectService::class);
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

        foreach ($this->slugObservers as $model => $observer) {
            if (class_exists($model)) {
                $model::observe($observer);
            }
        }

        // Scheduled commands (seo-engine:generate-pages, etc.) registered in TASK B3.
    }
}
