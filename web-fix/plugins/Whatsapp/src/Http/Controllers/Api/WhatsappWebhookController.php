<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Jobs\ProcessWhatsappWebhookJob;
use App\Plugins\Whatsapp\Models\WaSetting;
use App\Plugins\Whatsapp\Services\WebhookSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode'));
        $verifyToken = (string) $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge'));

        $settings = WaSetting::query()->latest('id')->first();
        if ($mode === 'subscribe' && $settings && hash_equals((string) $settings->verify_token, $verifyToken)) {
            if ($settings) {
                $settings->webhook_verified = true;
                $settings->save();
            }

            return response($challenge, 200);
        }
        return response('Forbidden', 403);
    }

    public function receive(Request $request, WebhookSignatureService $signatureService): JsonResponse
    {
        $settings = WaSetting::query()->latest('id')->first();
        $signature = (string) $request->header('X-Hub-Signature-256');
        $raw = (string) $request->getContent();
        $secret = (string) ($settings?->app_secret ?? '');

        if (! $signatureService->isValid($raw, $signature, $secret)) {
            return response()->json(['ok' => false], 401);
        }

        ProcessWhatsappWebhookJob::dispatch($request->all())->onQueue('whatsapp');
        return response()->json(['ok' => true], 200);
    }

}

