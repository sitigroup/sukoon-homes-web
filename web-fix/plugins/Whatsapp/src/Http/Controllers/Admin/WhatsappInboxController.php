<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Models\WaCannedReply;
use App\Plugins\Whatsapp\Models\WaConversation;
use App\Plugins\Whatsapp\Models\WaConversationNote;
use App\Plugins\Whatsapp\Models\WaMessage;
use App\Plugins\Whatsapp\Models\WaTemplate;
use App\Plugins\Whatsapp\Services\MetaGraphClient;
use App\Plugins\Whatsapp\Services\WhatsappConversationTagService;
use App\Plugins\Whatsapp\Services\WhatsappInboundMediaService;
use App\Plugins\Whatsapp\Services\WhatsappInboxContextService;
use App\Plugins\Whatsapp\Services\WhatsappMaintenanceMediaService;
use App\Plugins\Whatsapp\Support\WhatsappConversationTagCatalog;
use App\Plugins\Whatsapp\Support\WhatsappInboxAgentHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class WhatsappInboxController extends Controller
{
    private function denyUnlessInbox(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('inbox', 'whatsapp')) {
            abort(403);
        }
    }

    public function index(Request $request): View
    {
        $this->denyUnlessInbox();
        $search = (string) $request->query('search', '');
        $filter = (string) $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'open', 'pending', 'resolved', 'unassigned'], true)) {
            $filter = 'all';
        }

        $tagFilter = (string) $request->query('tag', '');
        if ($tagFilter !== '' && ! WhatsappConversationTagCatalog::isValid($tagFilter)) {
            $tagFilter = '';
        }

        $conversations = WaConversation::query()
            ->with(['messages', 'contact', 'tags'])
            ->when($filter === 'unassigned', fn ($q) => $q->whereNull('assigned_agent_id'))
            ->when($filter === 'mine', fn ($q) => $q->where('assigned_agent_id', auth()->id()))
            ->when(in_array($filter, ['open', 'pending', 'resolved'], true), fn ($q) => $q->where('status', $filter))
            ->when($tagFilter !== '' && Schema::hasTable('wa_conversation_tags'), fn ($q) => $q->whereHas(
                'tags',
                fn ($tq) => $tq->where('tag', $tagFilter)
            ))
            ->when($search, fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->whereHas('messages', fn ($mq) => $mq->where('body', 'like', "%{$search}%"))
                    ->orWhereHas('contact', fn ($cq) => $cq->where('phone', 'like', "%{$search}%"));
            }))
            ->orderByDesc('last_message_at')
            ->paginate(30)
            ->appends($request->query());

        $conversation = null;
        $tagService = app(WhatsappConversationTagService::class);

        if ($request->filled('conversation_id')) {
            $conversation = WaConversation::query()->with(['messages', 'contact', 'tags'])->find($request->integer('conversation_id'));
        } elseif ($conversations->count() > 0) {
            $conversation = WaConversation::query()
                ->with(['messages', 'contact', 'tags'])
                ->find(optional($conversations->first())->id);
        }

        if ($conversation) {
            $tagService->syncAutoTags($conversation);
            $conversation->load('tags');
        }

        $activeTags = $conversation
            ? $tagService->tagsForConversation($conversation->id)->all()
            : [];

        $templates = WaTemplate::query()->where('enabled', true)->orderBy('meta_template_name')->get();

        $inboxContext = app(WhatsappInboxContextService::class)
            ->forContact($conversation?->contact);

        $cannedReplies = Schema::hasTable('wa_canned_replies')
            ? WaCannedReply::query()->enabled()->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        $conversationNotes = collect();
        if ($conversation && Schema::hasTable('wa_conversation_notes')) {
            $conversationNotes = WaConversationNote::query()
                ->where('conversation_id', $conversation->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
        }

        $agentIds = $conversations->pluck('assigned_agent_id')
            ->merge([$conversation?->assigned_agent_id])
            ->filter()
            ->all();
        $agentNames = WhatsappInboxAgentHelper::namesByIds($agentIds);
        $assignableAgents = WhatsappInboxAgentHelper::assignableAgents();

        return view('whatsapp::admin.whatsapp.inbox', compact(
            'conversations',
            'conversation',
            'templates',
            'search',
            'filter',
            'tagFilter',
            'activeTags',
            'inboxContext',
            'cannedReplies',
            'conversationNotes',
            'assignableAgents',
            'agentNames'
        ));
    }

    public function assign(Request $request, WaConversation $conversation): RedirectResponse
    {
        $this->denyUnlessInbox();

        if ($request->boolean('assign_to_me')) {
            $agentId = auth()->id();
        } else {
            $agentId = $request->integer('assigned_agent_id') ?: null;
            if ($agentId && ! DB::table('users')->where('id', $agentId)->where('status', 1)->exists()) {
                return back()->with('error', __('whatsapp::whatsapp.assign_agent_invalid'));
            }
        }

        $conversation->assigned_agent_id = $agentId;
        $conversation->save();

        DB::table('wa_audit_log')->insert([
            'actor_id' => auth()->id(),
            'event' => 'assignment_change',
            'entity_type' => 'conversation',
            'entity_id' => $conversation->id,
            'meta_json' => json_encode(['assigned_agent_id' => $conversation->assigned_agent_id]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', __('whatsapp::whatsapp.saved'));
    }

    public function setStatus(Request $request, WaConversation $conversation): RedirectResponse
    {
        $this->denyUnlessInbox();
        $request->validate(['status' => ['required', 'in:open,pending,resolved']]);
        $conversation->status = $request->string('status')->toString();
        $conversation->save();
        return back()->with('success', __('whatsapp::whatsapp.saved'));
    }

    public function reply(Request $request, WaConversation $conversation, MetaGraphClient $meta): RedirectResponse
    {
        $this->denyUnlessInbox();
        $request->validate([
            'message' => ['nullable', 'string'],
            'template_id' => ['nullable', 'integer'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);
        $now = now();
        $windowOpen = $conversation->conversation_window_expires_at && $now->lessThanOrEqualTo($conversation->conversation_window_expires_at);
        $phone = optional($conversation->contact)->phone ?? null;

        if (! $phone) {
            return back()->with('error', __('whatsapp::whatsapp.contact_missing'));
        }

        if ($windowOpen && $request->hasFile('image')) {
            $file = $request->file('image');
            $mediaId = $meta->uploadMediaFromPath($file->getRealPath(), $file->getMimeType() ?: 'image/jpeg');
            if (! $mediaId) {
                return back()->with('error', __('whatsapp::whatsapp.media_upload_failed'));
            }

            $caption = $request->filled('message') ? $request->string('message')->toString() : null;
            $result = $meta->sendImage($phone, $mediaId, $caption);
            $status = ($result['ok'] ?? false) ? 'sent' : 'failed';

            $storedPath = null;
            if ($status === 'sent') {
                try {
                    $storedPath = app(WhatsappInboundMediaService::class)->storeFromUploadedFile($file);
                } catch (\Throwable) {
                    // outbound still sent
                }
            }

            $msg = WaMessage::query()->create([
                'conversation_id' => $conversation->id,
                'contact_id' => $conversation->contact_id,
                'wamid' => data_get($result, 'json.messages.0.id'),
                'direction' => 'out',
                'type' => 'image',
                'body' => $caption,
                'media_url' => $storedPath,
                'status' => $status,
                'error_json' => $status === 'failed' ? ($result['json'] ?? ['error' => 'send_failed']) : null,
            ]);
            DB::table('wa_audit_log')->insert([
                'actor_id' => auth()->id(),
                'event' => 'manual_image_reply',
                'entity_type' => 'message',
                'entity_id' => $msg->id,
                'meta_json' => json_encode(['window_open' => true, 'status' => $status]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('success', __('whatsapp::whatsapp.sent'));
        }

        if ($windowOpen && $request->filled('message')) {
            $result = $meta->sendText($phone, $request->string('message')->toString());
            $status = ($result['ok'] ?? false) ? 'sent' : 'failed';
            $msg = WaMessage::query()->create([
                'conversation_id' => $conversation->id,
                'contact_id' => $conversation->contact_id,
                'wamid' => data_get($result, 'json.messages.0.id'),
                'direction' => 'out',
                'type' => 'text',
                'body' => $request->string('message')->toString(),
                'status' => $status,
                'error_json' => $status === 'failed' ? ($result['json'] ?? ['error' => 'send_failed']) : null,
            ]);
            DB::table('wa_audit_log')->insert([
                'actor_id' => auth()->id(),
                'event' => 'manual_reply',
                'entity_type' => 'message',
                'entity_id' => $msg->id,
                'meta_json' => json_encode(['window_open' => true, 'status' => $status]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return back()->with('success', __('whatsapp::whatsapp.sent'));
        }

        if (! $windowOpen) {
            $template = WaTemplate::query()->find($request->integer('template_id'));
            if (! $template) {
                return back()->with('error', __('whatsapp::whatsapp.window_expired'));
            }
            $result = $meta->sendTemplate($phone, $template->meta_template_name, $template->language ?? 'en');
            $status = ($result['ok'] ?? false) ? 'sent' : 'failed';
            $msg = WaMessage::query()->create([
                'conversation_id' => $conversation->id,
                'contact_id' => $conversation->contact_id,
                'wamid' => data_get($result, 'json.messages.0.id'),
                'direction' => 'out',
                'type' => 'template',
                'template_key' => $template->internal_key,
                'body' => $template->meta_template_name,
                'status' => $status,
                'error_json' => $status === 'failed' ? ($result['json'] ?? ['error' => 'send_failed']) : null,
            ]);
            DB::table('wa_audit_log')->insert([
                'actor_id' => auth()->id(),
                'event' => 'template_fallback_reply',
                'entity_type' => 'message',
                'entity_id' => $msg->id,
                'meta_json' => json_encode(['window_open' => false, 'status' => $status]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return back()->with('success', __('whatsapp::whatsapp.sent'));
        }

        return back()->with('error', __('whatsapp::whatsapp.window_expired'));
    }

    public function addNote(Request $request, WaConversation $conversation): RedirectResponse
    {
        $this->denyUnlessInbox();

        if (! Schema::hasTable('wa_conversation_notes')) {
            return back()->with('error', __('whatsapp::whatsapp.notes_unavailable'));
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $note = WaConversationNote::query()->create([
            'conversation_id' => $conversation->id,
            'author_id' => auth()->id(),
            'body' => trim($validated['body']),
        ]);

        try {
            DB::table('wa_audit_log')->insert([
                'actor_id' => auth()->id(),
                'event' => 'internal_note_added',
                'entity_type' => 'conversation_note',
                'entity_id' => $note->id,
                'meta_json' => json_encode(['conversation_id' => $conversation->id]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // never block note save
        }

        return back()->with('success', __('whatsapp::whatsapp.note_saved'));
    }

    public function updateTags(Request $request, WaConversation $conversation): RedirectResponse
    {
        $this->denyUnlessInbox();

        $validated = $request->validate([
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'in:' . implode(',', WhatsappConversationTagCatalog::keys())],
        ]);

        $selected = $validated['tags'] ?? [];
        $tagService = app(WhatsappConversationTagService::class);
        $tagService->syncManualTags($conversation, $selected);
        $tagService->syncAutoTags($conversation->fresh(['contact', 'messages']));

        try {
            DB::table('wa_audit_log')->insert([
                'actor_id' => auth()->id(),
                'event' => 'conversation_tags_updated',
                'entity_type' => 'conversation',
                'entity_id' => $conversation->id,
                'meta_json' => json_encode(['tags' => $selected]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // never block
        }

        return back()->with('success', __('whatsapp::whatsapp.tags_saved'));
    }

    public function media(WaMessage $message): Response
    {
        $this->denyUnlessInbox();

        $path = (string) ($message->media_url ?? '');
        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $full = Storage::disk('local')->path($path);
        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';

        return response()->file($full, ['Content-Type' => $mime]);
    }

    public function attachMediaToMaintenance(Request $request, WaConversation $conversation, WaMessage $message): RedirectResponse
    {
        $this->denyUnlessInbox();

        if ((int) $message->conversation_id !== (int) $conversation->id) {
            abort(404);
        }

        $validated = $request->validate([
            'maintenance_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            app(WhatsappMaintenanceMediaService::class)->attachMessageToRequest(
                $message,
                (int) $validated['maintenance_id'],
                auth()->id()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', __('whatsapp::whatsapp.attach_mr_failed'));
        }

        DB::table('wa_audit_log')->insert([
            'actor_id' => auth()->id(),
            'event' => 'media_attached_to_mr',
            'entity_type' => 'message',
            'entity_id' => $message->id,
            'meta_json' => json_encode([
                'conversation_id' => $conversation->id,
                'maintenance_id' => (int) $validated['maintenance_id'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', __('whatsapp::whatsapp.attach_mr_success'));
    }
}

