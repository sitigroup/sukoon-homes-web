<?php

use App\Plugins\NearbyPlaces\Http\Controllers\Api\NearbyPlacesApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('nearby-places')->group(function () {
    Route::get('property/{propertyId}', [NearbyPlacesApiController::class, 'propertyNearby'])->whereNumber('propertyId');
});
