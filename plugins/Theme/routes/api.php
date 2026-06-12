<?php

use App\Plugins\Theme\Http\Controllers\Api\ThemeApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('theme')->group(function () {
    Route::get('public', [ThemeApiController::class, 'publicTheme']);
});
