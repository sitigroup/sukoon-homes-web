<?php

use App\Plugins\Theme\Http\Controllers\Admin\ThemeAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('appearance/theme')->name('appearance.theme.')->group(function () {
    Route::get('/', [ThemeAdminController::class, 'index'])->name('index');
    Route::get('preview-payload', [ThemeAdminController::class, 'previewPayload'])->name('preview');
    Route::post('draft', [ThemeAdminController::class, 'saveDraft'])->name('draft');
    Route::post('publish', [ThemeAdminController::class, 'publish'])->name('publish');
    Route::post('unpublish', [ThemeAdminController::class, 'unpublish'])->name('unpublish');
    Route::post('reset', [ThemeAdminController::class, 'resetDefault'])->name('reset');
    Route::post('preset', [ThemeAdminController::class, 'applyPreset'])->name('preset');
    Route::post('restore', [ThemeAdminController::class, 'restoreVersion'])->name('restore');
});
