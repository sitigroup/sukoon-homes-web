<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Models\WaSetting;
use App\Plugins\Whatsapp\Services\MetaGraphClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappSettingsController extends Controller
{
    private function denyUnlessSettings(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('settings', 'whatsapp')) {
            abort(403);
        }
    }

    public function index(): View
    {
        $this->denyUnlessSettings();
        $settings = WaSetting::query()->latest('id')->first();
        return view('whatsapp::admin.whatsapp.settings', compact('settings'));
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

        // Keep existing encrypted values when admin leaves secret fields empty.
        if ($request->filled('access_token')) {
            $settings->access_token = (string) $validated['access_token'];
        }
        if ($request->filled('app_secret')) {
            $settings->app_secret = (string) $validated['app_secret'];
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

