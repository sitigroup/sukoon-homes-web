<?php

use Illuminate\Support\Facades\Route;
use App\Plugins\Whatsapp\Http\Controllers\Api\WhatsappWebhookController;

Route::get('/whatsapp/webhook', [WhatsappWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsappWebhookController::class, 'receive']);

