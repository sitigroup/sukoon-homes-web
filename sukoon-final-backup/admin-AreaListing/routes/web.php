<?php

use App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'checkLogin'])->prefix('area-listing')->name('area-listing.')->group(function () {
    Route::get('/', [AreaListingAdminController::class, 'index'])->name('index');

    Route::get('areas', [AreaListingAdminController::class, 'areasIndex'])->name('areas.index');
    Route::get('areas/data', [AreaListingAdminController::class, 'areasData'])->name('areas.data');
    Route::post('areas', [AreaListingAdminController::class, 'storeArea'])->name('areas.store');
    Route::put('areas/{area}', [AreaListingAdminController::class, 'updateArea'])->name('areas.update');
    Route::delete('areas/{area}', [AreaListingAdminController::class, 'destroyArea'])->name('areas.destroy');
    Route::post('areas/{area}/restore', [AreaListingAdminController::class, 'restoreArea'])->name('areas.restore');
    Route::delete('areas/{area}/force-delete', [AreaListingAdminController::class, 'forceDeleteArea'])->name('areas.force-delete');

    Route::get('sub-areas', [AreaListingAdminController::class, 'subAreasIndex'])->name('sub-areas.index');
    Route::get('sub-areas/data', [AreaListingAdminController::class, 'subAreasData'])->name('sub-areas.data');
    Route::post('sub-areas', [AreaListingAdminController::class, 'storeSubArea'])->name('sub-areas.store');
    Route::put('sub-areas/{subArea}', [AreaListingAdminController::class, 'updateSubArea'])->name('sub-areas.update');
    Route::delete('sub-areas/{subArea}', [AreaListingAdminController::class, 'destroySubArea'])->name('sub-areas.destroy');
    Route::post('sub-areas/{subArea}/restore', [AreaListingAdminController::class, 'restoreSubArea'])->name('sub-areas.restore');
    Route::delete('sub-areas/{subArea}/force-delete', [AreaListingAdminController::class, 'forceDeleteSubArea'])->name('sub-areas.force-delete');

    Route::get('export-csv', [AreaListingAdminController::class, 'exportCsv'])->name('export-csv');
    Route::post('import-csv', [AreaListingAdminController::class, 'importCsv'])->name('import-csv');
    Route::post('import-csv/dry-run', [AreaListingAdminController::class, 'importCsv'])->name('import-csv.dry-run');
    Route::post('import-csv/confirm', [AreaListingAdminController::class, 'confirmCsvImport'])->name('import-csv.confirm');
    Route::post('import-csv/cancel', [AreaListingAdminController::class, 'cancelCsvImport'])->name('import-csv.cancel');
    Route::post('merge-preview', [AreaListingAdminController::class, 'mergePreview'])->name('merge.preview');
    Route::post('merge-confirm', [AreaListingAdminController::class, 'mergeConfirm'])->name('merge.confirm');
    Route::post('normalize-all', [AreaListingAdminController::class, 'normalizeAll'])->name('normalize-all');
    Route::post('suggestions/{id}/approve', [AreaListingAdminController::class, 'approveSuggestion'])->name('suggestions.approve');
    Route::post('suggestions/{id}/reject', [AreaListingAdminController::class, 'rejectSuggestion'])->name('suggestions.reject');
    Route::post('suggestions/{id}/merge', [AreaListingAdminController::class, 'mergeSuggestion'])->name('suggestions.merge');
    Route::post('repair-locations/dry-run', [AreaListingAdminController::class, 'repairLocationsDryRun'])->name('repair-locations.dry-run');
    Route::post('repair-locations/execute', [AreaListingAdminController::class, 'repairLocationsExecute'])->name('repair-locations.execute');
    Route::post('drift-check', [AreaListingAdminController::class, 'checkCityDrift'])->name('drift-check');
    Route::post('drift-check/sync-dry-run', [AreaListingAdminController::class, 'syncSnapshotsDryRun'])->name('drift-check.sync-dry-run');
    Route::post('drift-check/sync-execute', [AreaListingAdminController::class, 'syncSnapshotsExecute'])->name('drift-check.sync-execute');
    Route::post('resolve-coordinates', [AreaListingAdminController::class, 'resolveCoordinates'])->name('resolve-coordinates');
});
