<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Models\WaCannedReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappCannedReplyController extends Controller
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

        $replies = WaCannedReply::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('whatsapp::admin.whatsapp.canned-replies', compact('replies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->denyUnlessSettings();

        $validated = $this->validated($request);
        WaCannedReply::query()->create($validated);

        return back()->with('success', __('whatsapp::whatsapp.saved'));
    }

    public function update(Request $request, WaCannedReply $cannedReply): RedirectResponse
    {
        $this->denyUnlessSettings();

        $cannedReply->update($this->validated($request));

        return back()->with('success', __('whatsapp::whatsapp.saved'));
    }

    public function destroy(WaCannedReply $cannedReply): RedirectResponse
    {
        $this->denyUnlessSettings();

        $cannedReply->delete();

        return back()->with('success', __('whatsapp::whatsapp.canned_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body_en' => ['required', 'string', 'max:4096'],
            'body_hi' => ['nullable', 'string', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        return [
            'title' => $validated['title'],
            'body_en' => $validated['body_en'],
            'body_hi' => $validated['body_hi'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'enabled' => (bool) ($validated['enabled'] ?? false),
        ];
    }
}
