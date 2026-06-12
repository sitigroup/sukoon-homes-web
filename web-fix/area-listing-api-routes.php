<?php

use App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController;
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
    Route::get('permissions', [AreaListingApiController::class, 'permissions']);
    Route::post('states', [AreaListingApiController::class, 'storeState']);
    Route::post('cities', [AreaListingApiController::class, 'storeCity']);
    Route::post('areas', [AreaListingApiController::class, 'storeArea']);
    Route::post('sub-areas', [AreaListingApiController::class, 'storeSubArea']);
    Route::post('resolve-coordinates', [AreaListingApiController::class, 'resolveCoordinates']);
    Route::post('repair-locations/dry-run', [AreaListingAdminController::class, 'repairLocationsDryRun']);
    Route::post('drift-check', [AreaListingAdminController::class, 'checkCityDrift']);
});

Route::middleware(['auth:sanctum', 'throttle:area-suggest-area'])->prefix('location')->group(function () {
    Route::post('suggest-area', [AreaListingApiController::class, 'suggestArea']);
});

Route::middleware(['auth:sanctum', 'throttle:area-suggest-sub-area'])->prefix('location')->group(function () {
    Route::post('suggest-sub-area', [AreaListingApiController::class, 'suggestSubArea']);
});
