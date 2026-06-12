<?php

use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineDashboardController;
use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineRedirectsController;
use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('seo-engine')
    ->name('seo-engine.')
    ->middleware(['web', 'auth:sanctum', 'checkLogin'])
    ->group(function () {
        Route::get('/', [SeoEngineDashboardController::class, 'index'])->name('dashboard');
        Route::post('/regenerate-pages', [SeoEngineDashboardController::class, 'regeneratePages'])
            ->name('regenerate-pages');
        Route::post('/regenerate-sitemaps', [SeoEngineDashboardController::class, 'regenerateSitemaps'])
            ->name('regenerate-sitemaps');

        Route::get('/settings', [SeoEngineSettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SeoEngineSettingsController::class, 'store'])->name('settings.store');
        Route::post('/settings/clear-cache', [SeoEngineSettingsController::class, 'clearCache'])
            ->name('settings.clear-cache');

        Route::get('/redirects', [SeoEngineRedirectsController::class, 'index'])->name('redirects.index');
        Route::post('/redirects', [SeoEngineRedirectsController::class, 'store'])->name('redirects.store');
        Route::put('/redirects/{redirect}', [SeoEngineRedirectsController::class, 'update'])->name('redirects.update');
        Route::delete('/redirects/{redirect}', [SeoEngineRedirectsController::class, 'destroy'])->name('redirects.destroy');
        Route::post('/redirects/import', [SeoEngineRedirectsController::class, 'import'])->name('redirects.import');
    });
