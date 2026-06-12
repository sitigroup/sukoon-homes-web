<?php

use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineDashboardController;
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
    });
