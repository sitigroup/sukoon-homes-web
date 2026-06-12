<?php

namespace App\Plugins\Whatsapp\Jobs;

use App\Plugins\Whatsapp\Models\WaContact;
use App\Plugins\Whatsapp\Models\WaConversation;
use App\Plugins\Whatsapp\Models\WaMessage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Plugins\Whatsapp\Services\WhatsappConversationTagService;
use App\Plugins\Whatsapp\Services\WhatsappInboundMediaService;
use App\Plugins\Whatsapp\Services\WhatsappKeywordAutomationService;
use App\Plugins\Whatsapp\Services\WhatsappOffHoursService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProcessWhatsappWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [5, 20, 60];

    public function __construct(public array $payload)
    {
        $this->queue = 'whatsapp';
    }

    public function handle(): void
    {
        DB::table('wa_audit_log')->insert([
            'event' => 'webhook_received',
            'entity_type' => 'webhook',
            'meta_json' => json_encode($this->payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $entries = $this->payload['entry'] ?? [];
        foreach ($entries as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];

                foreach (($value['messages'] ?? []) as $incoming) {
                    $phone = $incoming['from'] ?? null;
                    if (! $phone) {
                        continue;
                    }
                    $contact = WaContact::query()->firstOrCreate(['phone' => $phone], ['opted_in' => true]);
                    $conversation = WaConversation::query()->firstOrCreate(['contact_id' => $contact->id]);

                    $lastInbound = Carbon::now();
                    $conversation->last_customer_message_at = $lastInbound;
                    $conversation->conversation_window_expires_at = $lastInbound->copy()->addHours(24);
                    $conversation->last_message_at = $lastInbound;
                    $conversation->save();

                    [$type, $body, $mediaPath] = $this->parseInboundContent($incoming);

                    WaMessage::query()->create([
                        'conversation_id' => $conversation->id,
                        'contact_id' => $contact->id,
                        'wamid' => $incoming['id'] ?? null,
                        'direction' => 'in',
                        'type' => $type,
                        'body' => $body,
                        'media_url' => $mediaPath,
                        'status' => 'sent',
                    ]);

                    if (Schema::hasTable('wa_conversation_tags')) {
                        try {
                            app(WhatsappConversationTagService::class)->syncAutoTags($conversation->fresh(['contact', 'messages']));
                        } catch (\Throwable) {
                            // never block webhook
                        }
                    }

                    if ($conversation->assigned_agent_id) {
                        try {
                            DB::table('wa_audit_log')->insert([
                                'actor_id' => null,
                                'event' => 'inbound_assigned_conversation',
                                'entity_type' => 'conversation',
                                'entity_id' => $conversation->id,
                                'meta_json' => json_encode([
                                    'assigned_agent_id' => $conversation->assigned_agent_id,
                                    'wamid' => $incoming['id'] ?? null,
                                ]),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } catch (\Throwable) {
                            // never block webhook
                        }
                    }

                    $offHoursSent = false;
                    try {
                        $offHoursSent = app(WhatsappOffHoursService::class)->maybeAutoReply(
                            $conversation->fresh(),
                            $contact->fresh()
                        );
                    } catch (\Throwable) {
                        // never block webhook
                    }

                    if ($type === 'text' && is_string($body) && trim($body) !== '') {
                        try {
                            app(WhatsappKeywordAutomationService::class)->maybeAutoReply(
                                $conversation->fresh(),
                                $contact->fresh(),
                                trim($body),
                                $offHoursSent
                            );
                        } catch (\Throwable) {
                            // never block webhook
                        }
                    }
                }

                foreach (($value['statuses'] ?? []) as $statusEvent) {
                    $wamid = $statusEvent['id'] ?? null;
                    if (! $wamid) {
                        continue;
                    }
                    $status = $statusEvent['status'] ?? 'sent';
                    $msg = WaMessage::query()->where('wamid', $wamid)->first();
                    if ($msg) {
                        $msg->status = in_array($status, ['delivered', 'read', 'failed', 'sent']) ? $status : 'sent';
                        $msg->error_json = $status === 'failed' ? ($statusEvent['errors'] ?? null) : $msg->error_json;
                        $msg->save();
                    }

                    DB::table('wa_message_status_log')->insert([
                        'message_id' => $msg?->id,
                        'wamid' => $wamid,
                        'status' => $status,
                        'payload_json' => json_encode($statusEvent),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * @return array{0:string,1:?string,2:?string}
     */
    private function parseInboundContent(array $incoming): array
    {
        $type = (string) ($incoming['type'] ?? 'text');
        $body = null;
        $mediaPath = null;

        if ($type === 'text') {
            $body = data_get($incoming, 'text.body');
        } elseif (in_array($type, ['image', 'document', 'video', 'audio', 'sticker'], true)) {
            $body = data_get($incoming, "{$type}.caption")
                ?? ($type === 'document' ? data_get($incoming, 'document.filename') : null);
            $mediaId = data_get($incoming, "{$type}.id");
            if ($mediaId) {
                try {
                    $mediaPath = app(WhatsappInboundMediaService::class)->storeFromMetaId((string) $mediaId);
                } catch (\Throwable $e) {
                    Log::warning('whatsapp.inbound_media_failed', [
                        'media_id' => $mediaId,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } else {
            $body = data_get($incoming, 'text.body');
        }

        return [$type, $body, $mediaPath];
    }
}

