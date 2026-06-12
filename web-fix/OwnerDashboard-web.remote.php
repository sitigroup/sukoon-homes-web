<?php

use App\Plugins\OwnerDashboard\Http\Controllers\Admin\OwnerTenancyAdminController;
use Illuminate\Support\Facades\Route;

// ── Admin routes ───────────────────────────────────────────────────────────
Route::prefix('owner-tenancies')
    ->name('admin.owner-tenancies.')
    ->middleware(['web', 'auth:sanctum', 'checkLogin'])
    ->group(function () {
        Route::get('/',                                [OwnerTenancyAdminController::class, 'index'])->name('index');
        Route::get('/create',                          [OwnerTenancyAdminController::class, 'create'])->name('create');
        Route::post('/',                               [OwnerTenancyAdminController::class, 'store'])->name('store');
        Route::post('/assign-owner',                   [OwnerTenancyAdminController::class, 'assignOwner'])->name('assign-owner');
        Route::get('/{owner_tenancy}/edit',            [OwnerTenancyAdminController::class, 'edit'])->name('edit');
        Route::put('/{owner_tenancy}',                 [OwnerTenancyAdminController::class, 'update'])->name('update');
        
        Route::post('/{owner_tenancy}/link-owner',       [OwnerTenancyAdminController::class, 'linkOwner'])->name('link-owner');
        Route::post('/{owner_tenancy}/status',         [OwnerTenancyAdminController::class, 'changeStatus'])->name('status');
        Route::post('/{owner_tenancy}/toggle-dashboard',[OwnerTenancyAdminController::class, 'toggleDashboard'])->name('toggle-dashboard');
        Route::post('/{owner_tenancy}/rent-reminder', [OwnerTenancyAdminController::class, 'sendRentReminder'])->name('rent-reminder');
    });

Route::prefix('owner-assignment-requests')
    ->name('admin.owner-assignment-requests.')
    ->middleware(['web', 'auth:sanctum', 'checkLogin'])
    ->group(function () {
        Route::get('/', [\App\Plugins\OwnerDashboard\Http\Controllers\Admin\OwnerAssignmentRequestAdminController::class, 'index'])->name('index');
        Route::get('/{owner_assignment_request}/approve', [\App\Plugins\OwnerDashboard\Http\Controllers\Admin\OwnerAssignmentRequestAdminController::class, 'approve'])->name('approve');
        Route::post('/{owner_assignment_request}/approve', [\App\Plugins\OwnerDashboard\Http\Controllers\Admin\OwnerAssignmentRequestAdminController::class, 'storeApprove'])->name('approve.store');
        Route::post('/{owner_assignment_request}/reject', [\App\Plugins\OwnerDashboard\Http\Controllers\Admin\OwnerAssignmentRequestAdminController::class, 'reject'])->name('reject');
    });

