<?php

use App\Plugins\SeoEngine\Http\Controllers\Api\SeoEngineSettingsApiController;
use Illuminate\Support\Facades\Route;

Route::get('/seo-engine/settings', [SeoEngineSettingsApiController::class, 'show']);
