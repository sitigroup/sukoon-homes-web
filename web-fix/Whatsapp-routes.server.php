<?php

use Illuminate\Support\Facades\Route;
use App\Plugins\Whatsapp\Http\Controllers\Admin\WhatsappEventController;
use App\Plugins\Whatsapp\Http\Controllers\Admin\WhatsappSettingsController;
use App\Plugins\Whatsapp\Http\Controllers\Admin\WhatsappTemplateController;
use App\Plugins\Whatsapp\Http\Controllers\Admin\WhatsappDeliveryController;
use App\Plugins\Whatsapp\Http\Controllers\Admin\WhatsappInboxController;
use App\Plugins\Whatsapp\Http\Controllers\Admin\WhatsappCannedReplyController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('whatsapp')->group(function () {
        Route::get('/settings', [WhatsappSettingsController::class, 'index'])
            ->name('whatsapp.settings.index');
        Route::post('/settings', [WhatsappSettingsController::class, 'store'])
            ->name('whatsapp.settings.store');
        Route::post('/settings/test-connection', [WhatsappSettingsController::class, 'testConnection'])
            ->name('whatsapp.settings.test');

        Route::get('/templates', [WhatsappTemplateController::class, 'index'])
            ->name('whatsapp.templates.index');
        Route::post('/templates/sync', [WhatsappTemplateController::class, 'sync'])
            ->name('whatsapp.templates.sync');
        Route::post('/templates/{template}/toggle', [WhatsappTemplateController::class, 'toggle'])
            ->name('whatsapp.templates.toggle');
        Route::post('/templates/{template}/map', [WhatsappTemplateController::class, 'mapInternalKey'])
            ->name('whatsapp.templates.map');

        Route::get('/events', [WhatsappEventController::class, 'index'])
            ->name('whatsapp.events.index');
        Route::get('/events/create', [WhatsappEventController::class, 'create'])
            ->name('whatsapp.events.create');
        Route::post('/events', [WhatsappEventController::class, 'store'])
            ->name('whatsapp.events.store');
        Route::get('/events/{event}/edit', [WhatsappEventController::class, 'edit'])
            ->name('whatsapp.events.edit');
        Route::put('/events/{event}', [WhatsappEventController::class, 'update'])
            ->name('whatsapp.events.update');
        Route::delete('/events/{event}', [WhatsappEventController::class, 'destroy'])
            ->name('whatsapp.events.destroy');
        Route::post('/events/send', [WhatsappEventController::class, 'sendManual'])
            ->name('whatsapp.events.send');

        Route::get('/delivery', [WhatsappDeliveryController::class, 'index'])
            ->name('whatsapp.delivery.index');

        Route::get('/canned-replies', [WhatsappCannedReplyController::class, 'index'])
            ->name('whatsapp.canned.index');
        Route::post('/canned-replies', [WhatsappCannedReplyController::class, 'store'])
            ->name('whatsapp.canned.store');
        Route::put('/canned-replies/{cannedReply}', [WhatsappCannedReplyController::class, 'update'])
            ->name('whatsapp.canned.update');
        Route::delete('/canned-replies/{cannedReply}', [WhatsappCannedReplyController::class, 'destroy'])
            ->name('whatsapp.canned.destroy');

        Route::get('/inbox', [WhatsappInboxController::class, 'index'])
            ->name('whatsapp.inbox.index');
        Route::post('/inbox/{conversation}/assign', [WhatsappInboxController::class, 'assign'])
            ->name('whatsapp.inbox.assign');
        Route::post('/inbox/{conversation}/status', [WhatsappInboxController::class, 'setStatus'])
            ->name('whatsapp.inbox.status');
        Route::post('/inbox/{conversation}/reply', [WhatsappInboxController::class, 'reply'])
            ->name('whatsapp.inbox.reply');
        Route::post('/inbox/{conversation}/notes', [WhatsappInboxController::class, 'addNote'])
            ->name('whatsapp.inbox.notes');
        Route::post('/inbox/{conversation}/tags', [WhatsappInboxController::class, 'updateTags'])
            ->name('whatsapp.inbox.tags');
        Route::get('/inbox/media/{message}', [WhatsappInboxController::class, 'media'])
            ->name('whatsapp.inbox.media');
        Route::post('/inbox/{conversation}/messages/{message}/attach-maintenance', [WhatsappInboxController::class, 'attachMediaToMaintenance'])
            ->name('whatsapp.inbox.attach-maintenance');
    });
});

