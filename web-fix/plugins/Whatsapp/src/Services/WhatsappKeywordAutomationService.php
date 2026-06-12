<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WaContact;
use App\Plugins\Whatsapp\Models\WaConversation;
use App\Plugins\Whatsapp\Models\WaConversationTag;
use App\Plugins\Whatsapp\Models\WaMessage;
use App\Plugins\Whatsapp\Models\WaSetting;
use App\Plugins\Whatsapp\Support\WhatsappConversationTagCatalog;
use App\Plugins\Whatsapp\Support\WhatsappKeywordCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsappKeywordAutomationService
{
    /**
     * Send a keyword auto-reply when inbound text matches rent / repair / agreement rules.
     */
    public function maybeAutoReply(
        WaConversation $conversation,
        WaContact $contact,
        string $inboundText,
        bool $skipIfOffHoursReply = false
    ): bool {
        try {
            $settings = WaSetting::query()->latest('id')->first();
            if (! $settings?->keyword_automation_enabled) {
                return false;
            }

            if ($skipIfOffHoursReply) {
                return false;
            }

            if ($settings->off_hours_enabled && ! app(WhatsappOffHoursService::class)->isBusinessHours($settings)) {
                return false;
            }

            if ((int) ($conversation->assigned_agent_id ?? 0) > 0) {
                return false;
            }

            $rule = WhatsappKeywordCatalog::match($inboundText);
            if (! $rule) {
                return false;
            }

            if (! $this->shouldSendToday($conversation, $settings)) {
                $this->applyTags($conversation, $rule['tags']);

                return false;
            }

            $text = trim((string) ($settings->{$rule['reply_setting']} ?? ''));
            if ($text === '') {
                $text = $rule['default_reply'];
            }

            $phone = (string) ($contact->phone ?? '');
            if ($phone === '' || $text === '') {
                return false;
            }

            $result = app(MetaGraphClient::class)->sendText($phone, $text);
            $status = ($result['ok'] ?? false) ? 'sent' : 'failed';

            WaMessage::query()->create([
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'wamid' => data_get($result, 'json.messages.0.id'),
                'direction' => 'out',
                'type' => 'text',
                'body' => $text,
                'status' => $status,
                'error_json' => $status === 'failed' ? ($result['json'] ?? ['error' => 'keyword_auto_reply_failed']) : null,
            ]);

            $conversation->keyword_auto_reply_at = now();
            if ($conversation->status === 'open') {
                $conversation->status = 'pending';
            }
            $conversation->last_message_at = now();
            $conversation->save();

            $this->applyTags($conversation, $rule['tags']);

            DB::table('wa_audit_log')->insert([
                'event' => 'keyword_auto_reply',
                'entity_type' => 'conversation',
                'entity_id' => $conversation->id,
                'meta_json' => json_encode([
                    'keyword' => $rule['key'],
                    'status' => $status,
                    'tags' => $rule['tags'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $status === 'sent';
        } catch (\Throwable $e) {
            Log::warning('whatsapp.keyword_auto_reply_failed', [
                'conversation_id' => $conversation->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  array<int, string>  $tags
     */
    private function applyTags(WaConversation $conversation, array $tags): void
    {
        if (! Schema::hasTable('wa_conversation_tags')) {
            return;
        }

        foreach ($tags as $tag) {
            if (! WhatsappConversationTagCatalog::isValid($tag)) {
                continue;
            }

            WaConversationTag::query()->firstOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'tag' => $tag,
                ],
                ['auto_applied' => true]
            );
        }
    }

    private function shouldSendToday(WaConversation $conversation, WaSetting $settings): bool
    {
        if (! $conversation->keyword_auto_reply_at) {
            return true;
        }

        $tz = trim((string) ($settings->business_timezone ?? 'Asia/Kolkata'));
        $tz = $tz !== '' ? $tz : 'Asia/Kolkata';
        $today = Carbon::now($tz)->toDateString();
        $lastDay = Carbon::parse($conversation->keyword_auto_reply_at)->timezone($tz)->toDateString();

        return $lastDay !== $today;
    }
}
