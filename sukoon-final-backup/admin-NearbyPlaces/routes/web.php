<?php

use App\Plugins\NearbyPlaces\Http\Controllers\Admin\NearbyPlacesAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'checkLogin'])->prefix('nearby-places')->name('nearby-places.')->group(function () {
    Route::get('/', [NearbyPlacesAdminController::class, 'index'])->name('index');

    Route::post('categories/bulk', [NearbyPlacesAdminController::class, 'bulkStoreCategories'])->name('categories.bulk-store');
    Route::post('categories/bulk-archive', [NearbyPlacesAdminController::class, 'bulkArchiveCategories'])->name('categories.bulk-archive');
    Route::post('categories/bulk-restore', [NearbyPlacesAdminController::class, 'bulkRestoreCategories'])->name('categories.bulk-restore');
    Route::post('categories/bulk-patch', [NearbyPlacesAdminController::class, 'bulkPatchCategories'])->name('categories.bulk-patch');
    Route::put('categories/quick-update', [NearbyPlacesAdminController::class, 'quickUpdateCategories'])->name('categories.quick-update');

    Route::post('categories', [NearbyPlacesAdminController::class, 'storeCategory'])->name('categories.store');
    Route::put('categories/{category}', [NearbyPlacesAdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('categories/{category}', [NearbyPlacesAdminController::class, 'destroyCategory'])->name('categories.destroy');
    Route::post('categories/{categoryId}/restore', [NearbyPlacesAdminController::class, 'restoreCategory'])->whereNumber('categoryId')->name('categories.restore');

    Route::post('settings', [NearbyPlacesAdminController::class, 'updateSettings'])->name('settings.update');
    Route::get('api-key/check', [NearbyPlacesAdminController::class, 'checkApiKey'])->name('api-key.check');

    Route::post('refresh-property', [NearbyPlacesAdminController::class, 'refreshProperty'])->name('refresh-property');
    Route::post('clear-property-cache', [NearbyPlacesAdminController::class, 'clearPropertyCache'])->name('clear-property-cache');
    Route::post('refresh-all-cached', [NearbyPlacesAdminController::class, 'refreshAllCached'])->name('refresh-all-cached');
    Route::post('clear-all-cache', [NearbyPlacesAdminController::class, 'clearAllCache'])->name('clear-all-cache');

    Route::post('review-places', [NearbyPlacesAdminController::class, 'reviewPlaces'])->name('review-places');
    Route::post('overrides/quick', [NearbyPlacesAdminController::class, 'quickOverride'])->name('overrides.quick');
    Route::post('overrides', [NearbyPlacesAdminController::class, 'storeOverride'])->name('overrides.store');
    Route::delete('overrides/{override}', [NearbyPlacesAdminController::class, 'destroyOverride'])->name('overrides.destroy');
});
