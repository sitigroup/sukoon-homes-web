<?php

use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineContentReviewController;
use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineDashboardController;
use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEnginePagesController;
use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineQaPagesController;
use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineRedirectsController;
use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineSettingsController;
use App\Plugins\SeoEngine\Http\Controllers\Admin\SeoEngineTemplatesController;
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

        Route::get('/templates', [SeoEngineTemplatesController::class, 'index'])->name('templates.index');
        Route::post('/templates', [SeoEngineTemplatesController::class, 'store'])->name('templates.store');

        Route::get('/pages', [SeoEnginePagesController::class, 'index'])->name('pages.index');
        Route::get('/pages/export', [SeoEnginePagesController::class, 'export'])->name('pages.export');
        Route::post('/pages/bulk', [SeoEnginePagesController::class, 'bulk'])->name('pages.bulk');
        Route::get('/pages/{page}/edit', [SeoEnginePagesController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [SeoEnginePagesController::class, 'update'])->name('pages.update');

        Route::get('/content-review', [SeoEngineContentReviewController::class, 'index'])->name('content.index');
        Route::post('/content-review/{page}/approve', [SeoEngineContentReviewController::class, 'approve'])->name('content.approve');
        Route::post('/content-review/{page}/regenerate', [SeoEngineContentReviewController::class, 'regenerate'])->name('content.regenerate');
        Route::post('/content-review/{page}/lock', [SeoEngineContentReviewController::class, 'lock'])->name('content.lock');

        Route::get('/qa', [SeoEngineQaPagesController::class, 'index'])->name('qa.index');
        Route::get('/qa/create', [SeoEngineQaPagesController::class, 'create'])->name('qa.create');
        Route::post('/qa', [SeoEngineQaPagesController::class, 'store'])->name('qa.store');
        Route::get('/qa/{qaPage}/edit', [SeoEngineQaPagesController::class, 'edit'])->name('qa.edit');
        Route::put('/qa/{qaPage}', [SeoEngineQaPagesController::class, 'update'])->name('qa.update');
        Route::delete('/qa/{qaPage}', [SeoEngineQaPagesController::class, 'destroy'])->name('qa.destroy');
    });
