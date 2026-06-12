<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Models\WaSetting;
use App\Plugins\Whatsapp\Services\MetaGraphClient;
use App\Plugins\Whatsapp\Services\WhatsappWebhookHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WhatsappSettingsController extends Controller
{
    private function denyUnlessSettings(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('settings', 'whatsapp')) {
            abort(403);
        }
    }

    public function index(WhatsappWebhookHealthService $healthService): View
    {
        $this->denyUnlessSettings();
        $settings = WaSetting::query()->latest('id')->first();
        $webhookHealth = $healthService->snapshot();

        return view('whatsapp::admin.whatsapp.settings', compact('settings', 'webhookHealth'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->denyUnlessSettings();
        $validated = $request->validate([
            'meta_app_id' => ['nullable', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'phone_number_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string'],
            'verify_token' => ['nullable', 'string', 'max:255'],
            'app_secret' => ['nullable', 'string'],
            'environment' => ['nullable', 'in:test_number,sandbox_waba,production_waba'],
            'environment_mode' => ['nullable', 'in:test_number,sandbox_waba,production_waba'],
            'off_hours_enabled' => ['nullable', 'boolean'],
            'business_hours_start' => ['nullable', 'date_format:H:i'],
            'business_hours_end' => ['nullable', 'date_format:H:i'],
            'business_timezone' => ['nullable', 'string', 'max:64'],
            'off_hours_reply_text' => ['nullable', 'string', 'max:1000'],
            'keyword_automation_enabled' => ['nullable', 'boolean'],
            'keyword_reply_rent' => ['nullable', 'string', 'max:1000'],
            'keyword_reply_repair' => ['nullable', 'string', 'max:1000'],
            'keyword_reply_agreement' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = WaSetting::query()->latest('id')->first() ?? new WaSetting();
        $settings->meta_app_id = $validated['meta_app_id'] ?? null;
        $settings->waba_id = $validated['waba_id'] ?? null;
        $settings->phone_number_id = $validated['phone_number_id'] ?? null;
        $settings->verify_token = $validated['verify_token'] ?? null;
        $settings->environment_mode = $validated['environment']
            ?? $validated['environment_mode']
            ?? $settings->environment_mode
            ?? 'test_number';

        if ($request->filled('access_token')) {
            $settings->access_token = (string) $validated['access_token'];
        }
        if ($request->filled('app_secret')) {
            $settings->app_secret = (string) $validated['app_secret'];
        }

        if (Schema::hasColumn('wa_settings', 'off_hours_enabled')) {
            $settings->off_hours_enabled = $request->boolean('off_hours_enabled');
            $settings->business_hours_start = $validated['business_hours_start'] ?? $settings->business_hours_start ?? '09:00';
            $settings->business_hours_end = $validated['business_hours_end'] ?? $settings->business_hours_end ?? '18:00';
            $settings->business_timezone = $validated['business_timezone'] ?? $settings->business_timezone ?? 'Asia/Kolkata';
            $settings->off_hours_reply_text = $validated['off_hours_reply_text'] ?? null;
        }

        if (Schema::hasColumn('wa_settings', 'keyword_automation_enabled')) {
            $settings->keyword_automation_enabled = $request->boolean('keyword_automation_enabled');
            $settings->keyword_reply_rent = $validated['keyword_reply_rent'] ?? null;
            $settings->keyword_reply_repair = $validated['keyword_reply_repair'] ?? null;
            $settings->keyword_reply_agreement = $validated['keyword_reply_agreement'] ?? null;
        }

        $settings->save();

        return back()->with('success', __('whatsapp::whatsapp.saved'));
    }

    public function testConnection(MetaGraphClient $meta): RedirectResponse
    {
        $this->denyUnlessSettings();
        $result = $meta->testConnection();
        $settings = WaSetting::query()->latest('id')->first();
        if ($settings) {
            $settings->webhook_verified = (bool) ($result['ok'] ?? false);
            $settings->last_tested_at = now();
            $settings->save();
        }

        if ($result['ok'] ?? false) {
            return back()->with('success', __('whatsapp::whatsapp.connection_ok'));
        }
        $message = (string) ($result['message'] ?? __('whatsapp::whatsapp.connection_failed'));
        if (stripos($message, 'access token') !== false || stripos($message, 'session has expired') !== false) {
            $message .= ' ' . __('whatsapp::whatsapp.token_expired_hint');
        }

        return back()->with('error', $message);
    }
}
