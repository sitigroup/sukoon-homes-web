<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaTemplate;
use App\Plugins\Whatsapp\Services\WhatsappEventRegistryService;
use App\Plugins\Whatsapp\Support\WhatsappEventCatalog;
use App\Plugins\Whatsapp\Support\WhatsappPhoneHelper;
use App\Plugins\Whatsapp\Support\WaTemplateVariableHelper;
use App\Plugins\Whatsapp\Support\WhatsappEventVariableMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WhatsappEventController extends Controller
{
    public function __construct(
        private readonly WhatsappEventRegistryService $registry,
    ) {}

    private function denyUnlessEvents(): void
    {
        if (! function_exists('has_permissions')) {
            abort(403);
        }

        if (has_permissions('events', 'whatsapp') || has_permissions('templates', 'whatsapp')) {
            return;
        }

        abort(403);
    }

    public function index(): View
    {
        $this->denyUnlessEvents();

        $events = $this->registry->allEventsForAdmin();
        $templates = WaTemplate::query()->orderBy('meta_template_name')->get();

        $eventVariableMeta = WhatsappEventVariableMeta::forSendableEvents($events);

        return view('whatsapp::admin.whatsapp.events', compact('events', 'templates', 'eventVariableMeta'));
    }

    public function create(): View
    {
        $this->denyUnlessEvents();

        return view('whatsapp::admin.whatsapp.event-form', [
            'event' => new WaEventMap(['event_type' => 'custom', 'language' => 'en', 'enabled' => false]),
            'templates' => WaTemplate::query()->orderBy('meta_template_name')->get(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->denyUnlessEvents();

        $validated = $request->validate([
            'event_key' => ['required', 'string', 'max:64', 'unique:wa_event_map,event_key', 'regex:/^[a-z][a-z0-9_]{2,63}$/'],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'template_id' => ['nullable', 'integer', 'exists:wa_templates,id'],
            'enabled' => ['nullable', 'boolean'],
            'language' => ['required', 'in:en,hi'],
        ]);

        if (WhatsappEventCatalog::isSystemKey($validated['event_key'])) {
            return back()->withInput()->with('error', __('whatsapp::whatsapp.event_key_reserved'));
        }

        try {
            $map = $this->persistMap(new WaEventMap(), $validated, 'custom');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit('event_created', $map);

        return redirect()->route('whatsapp.events.index')->with('success', __('whatsapp::whatsapp.event_created'));
    }

    public function edit(WaEventMap $event): View
    {
        $this->denyUnlessEvents();

        $event->load('template');

        return view('whatsapp::admin.whatsapp.event-form', [
            'event' => $event,
            'templates' => WaTemplate::query()->orderBy('meta_template_name')->get(),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, WaEventMap $event): RedirectResponse
    {
        $this->denyUnlessEvents();

        $isSystem = $event->event_type === 'system' || WhatsappEventCatalog::isSystemKey($event->event_key);

        $rules = [
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'template_id' => ['nullable', 'integer', 'exists:wa_templates,id'],
            'enabled' => ['nullable', 'boolean'],
            'language' => ['required', 'in:en,hi'],
        ];

        if (! $isSystem) {
            $rules['event_key'] = ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]{2,63}$/', 'unique:wa_event_map,event_key,' . $event->id];
        }

        $validated = $request->validate($rules);

        if (! $isSystem && WhatsappEventCatalog::isSystemKey($validated['event_key'] ?? '')) {
            return back()->withInput()->with('error', __('whatsapp::whatsapp.event_key_reserved'));
        }

        $before = $event->only(['event_key', 'display_name', 'template_id', 'enabled', 'language']);

        if ($isSystem) {
            unset($validated['event_key']);
            $validated['event_type'] = 'system';
        } else {
            $validated['event_type'] = 'custom';
        }

        try {
            $map = $this->persistMap($event, $validated, $isSystem ? 'system' : 'custom');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit('event_updated', $map, $before);

        return redirect()->route('whatsapp.events.index')->with('success', __('whatsapp::whatsapp.saved'));
    }

    public function destroy(Request $request, WaEventMap $event): RedirectResponse
    {
        $this->denyUnlessEvents();

        if ($event->event_type === 'system' || WhatsappEventCatalog::isSystemKey($event->event_key)) {
            return back()->with('error', __('whatsapp::whatsapp.system_event_no_delete'));
        }

        if (! $request->boolean('confirm_delete')) {
            return back()->with('error', __('whatsapp::whatsapp.confirm_delete_required'));
        }

        $this->audit('event_deleted', $event);
        $event->delete();

        return redirect()->route('whatsapp.events.index')->with('success', __('whatsapp::whatsapp.event_deleted'));
    }

    public function sendManual(Request $request): RedirectResponse
    {
        $this->denyUnlessEvents();

        $base = $request->validate([
            'event_key' => ['required', 'string', 'exists:wa_event_map,event_key'],
            'phone' => ['required', 'string', 'max:20'],
            'customer_type' => ['nullable', 'string', 'max:32'],
            'customer_id' => ['nullable', 'integer'],
        ]);

        $map = WaEventMap::query()->with('template')->where('event_key', $base['event_key'])->first();
        $template = $map?->template;
        if (! $template) {
            return back()->with('error', __('whatsapp::whatsapp.template_not_found'));
        }

        $expected = WaTemplateVariableHelper::bodyVariableCount($template);
        $validated = array_merge(
            $base,
            $request->validate(WaTemplateVariableHelper::validationRulesForCount($expected))
        );

        $phone = WhatsappPhoneHelper::toMetaRecipient($validated['phone']);
        if ($phone === '') {
            return back()->with('error', __('whatsapp::whatsapp.contact_missing'));
        }

        $vars = WaTemplateVariableHelper::collectFromValidated($validated, $expected);

        try {
            $result = \Whatsapp::notify($validated['event_key'], [
                'phone' => $phone,
                'customer_type' => $validated['customer_type'] ?? 'manual',
                'customer_id' => $validated['customer_id'] ?? null,
            ], $vars);

            $this->audit('manual_template_send', null, null, [
                'event_key' => $validated['event_key'],
                'phone' => substr($phone, 0, 4) . '****',
                'result' => $result['status'] ?? 'unknown',
            ]);

            return back()
                ->with('success', __('whatsapp::whatsapp.manual_send_processed'))
                ->with('whatsapp_feedback', $result);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp manual send failed', ['error' => $e->getMessage()]);

            return back()->with('error', __('whatsapp::whatsapp.manual_send_failed'));
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistMap(WaEventMap $map, array $validated, string $eventType): WaEventMap
    {
        $enabled = (bool) ($validated['enabled'] ?? false);
        $templateId = $validated['template_id'] ?? null;

        if ($enabled && ! $templateId) {
            throw new \InvalidArgumentException(__('whatsapp::whatsapp.event_enabled_without_template'));
        }

        if ($templateId) {
            $template = WaTemplate::query()->find($templateId);
            if (! $template) {
                throw new \InvalidArgumentException(__('whatsapp::whatsapp.template_not_found'));
            }
            if ($enabled && strtolower((string) $template->status) !== 'approved') {
                throw new \InvalidArgumentException(__('whatsapp::whatsapp.template_not_approved'));
            }
        }

        $payload = [
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'template_id' => $templateId,
            'enabled' => $enabled,
            'language' => $validated['language'],
            'event_type' => $eventType,
        ];

        if (isset($validated['event_key'])) {
            $payload['event_key'] = $validated['event_key'];
        }

        $map->fill($payload);
        $map->save();

        return $map->fresh(['template']);
    }

    private function audit(string $event, ?WaEventMap $map, ?array $before = null, ?array $extra = null): void
    {
        try {
            DB::table('wa_audit_log')->insert([
                'actor_id' => auth()->id(),
                'event' => $event,
                'entity_type' => 'event',
                'entity_id' => $map?->id,
                'meta_json' => json_encode(array_filter([
                    'event_key' => $map?->event_key,
                    'before' => $before,
                    'after' => $map?->only(['event_key', 'display_name', 'template_id', 'enabled', 'language', 'event_type']),
                    'extra' => $extra,
                ])),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // never block admin UI
        }
    }
}
