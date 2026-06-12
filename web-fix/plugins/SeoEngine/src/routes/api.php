<?php

use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineRedirectApiController;
use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineSettingsApiController;
use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineSitemapApiController;
use Illuminate\Support\Facades\Route;

Route::get('/seo-engine/settings', [SeoEngineSettingsApiController::class, 'show']);
Route::get('/seo-engine/redirect', [SeoEngineRedirectApiController::class, 'show']);
Route::get('/seo-engine/rent-sitemap', [SeoEngineSitemapApiController::class, 'rentPages']);
