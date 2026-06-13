<?php

use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineLeadApiController;
use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngine404LogApiController;
use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineBotFilesApiController;
use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEnginePageApiController;
use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineQaPageApiController;
use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineRedirectApiController;
use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineSettingsApiController;
use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineSitemapApiController;
use Illuminate\Support\Facades\Route;

Route::post('/seo-engine/leads', [SeoEngineLeadApiController::class, 'store']);
Route::get('/seo-engine/settings', [SeoEngineSettingsApiController::class, 'show']);
Route::get('/seo-engine/redirect', [SeoEngineRedirectApiController::class, 'show']);
Route::get('/seo-engine/rent-sitemap', [SeoEngineSitemapApiController::class, 'rentPages']);
Route::get('/seo-engine/qa-page', [SeoEngineQaPageApiController::class, 'show']);
Route::get('/seo-engine/qa-sitemap', [SeoEngineQaPageApiController::class, 'sitemap']);
Route::get('/seo-engine/page', [SeoEnginePageApiController::class, 'show']);
Route::get('/seo-engine/paths', [SeoEnginePageApiController::class, 'paths']);
Route::get('/seo-engine/robots-txt', [SeoEngineBotFilesApiController::class, 'robots']);
Route::get('/seo-engine/llms-txt', [SeoEngineBotFilesApiController::class, 'llms']);
Route::post('/seo-engine/404-log', [SeoEngine404LogApiController::class, 'store']);
