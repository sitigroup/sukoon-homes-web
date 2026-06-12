<?php

use App\Plugins\AreaListing\Http\Controllers\Api\AreaListingApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('area-listing')->group(function () {
    Route::get('states', [AreaListingApiController::class, 'states']);
    Route::get('cities', [AreaListingApiController::class, 'cities']);
    Route::get('areas', [AreaListingApiController::class, 'areas']);
    Route::get('sub-areas', [AreaListingApiController::class, 'subAreas']);
});

Route::prefix('location')->group(function () {
    Route::get('states', [AreaListingApiController::class, 'states']);
    Route::get('cities', [AreaListingApiController::class, 'cities']);
    Route::get('areas', [AreaListingApiController::class, 'areas']);
    Route::get('sub-areas', [AreaListingApiController::class, 'subAreas']);
});

Route::middleware('auth:sanctum')->prefix('area-listing')->group(function () {
    Route::post('states', [AreaListingApiController::class, 'storeState']);
    Route::post('cities', [AreaListingApiController::class, 'storeCity']);
    Route::post('areas', [AreaListingApiController::class, 'storeArea']);
    Route::post('sub-areas', [AreaListingApiController::class, 'storeSubArea']);
});

Route::middleware(['auth:sanctum', 'throttle:5,60'])->prefix('location')->group(function () {
    Route::post('suggest-area', [AreaListingApiController::class, 'suggestArea']);
    Route::post('suggest-sub-area', [AreaListingApiController::class, 'suggestSubArea']);
});
