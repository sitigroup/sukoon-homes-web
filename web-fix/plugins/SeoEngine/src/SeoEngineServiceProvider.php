<?php

namespace App\Plugins\SeoEngine;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Plugins\SeoEngine\Console\BuildSitemapsCommand;
use App\Plugins\SeoEngine\Console\Digest404Command;
use App\Plugins\SeoEngine\Console\GenerateContentCommand;
use App\Plugins\SeoEngine\Console\GeneratePagesCommand;
use App\Plugins\SeoEngine\Console\SeedQaCommand;
use App\Plugins\SeoEngine\Observers\ArticleSlugObserver;
use App\Plugins\SeoEngine\Observers\ProjectSlugObserver;
use App\Plugins\SeoEngine\Observers\PropertySeoPageObserver;
use App\Plugins\SeoEngine\Observers\PropertySlugObserver;
use App\Plugins\SeoEngine\Services\SeoEnginePageDataService;
use App\Plugins\SeoEngine\Services\SeoEnginePageGeneratorService;
use App\Plugins\SeoEngine\Services\SeoEnginePageTouchService;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Pagination\Paginator;

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
        $this->app->singleton(\App\Plugins\SeoEngine\Services\SeoEngineRentSitemapService::class);
        $this->app->singleton(SeoEnginePageDataService::class);
        $this->app->singleton(SeoEnginePageTouchService::class);
        $this->app->singleton(\App\Plugins\SeoEngine\Services\SeoEngineContentService::class);
    }

    public function boot(): void
    {
        // Admin panel uses Bootstrap 5 — Laravel defaults to Tailwind pagination SVGs without CSS.
        Paginator::useBootstrapFive();

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

        if (class_exists(\App\Models\Property::class)) {
            \App\Models\Property::observe(PropertySeoPageObserver::class);
        }

        $this->commands([GeneratePagesCommand::class, BuildSitemapsCommand::class, GenerateContentCommand::class, SeedQaCommand::class, Digest404Command::class]);

        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('seo-engine:generate-pages')->dailyAt('02:00')->withoutOverlapping();
                $schedule->command('seo-engine:digest-404')->weeklyOn(1, '06:30')->withoutOverlapping();
            });
        }
    }
}
