<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WaConversation;
use App\Plugins\Whatsapp\Models\WaConversationTag;
use App\Plugins\Whatsapp\Support\WhatsappConversationTagCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsappConversationTagService
{
    public function syncAutoTags(WaConversation $conversation): void
    {
        if (! Schema::hasTable('wa_conversation_tags')) {
            return;
        }

        try {
            $conversation->loadMissing('contact');
            $tags = WhatsappConversationTagCatalog::autoFromCustomerType($conversation->contact?->customer_type);

            $context = app(WhatsappInboxContextService::class)->forContact($conversation->contact);
            if (! empty($context['maintenance'])) {
                $tags[] = 'maintenance';
            }

            $tags = array_merge($tags, $this->tagsFromRecentInboundText($conversation));

            foreach (array_unique($tags) as $tag) {
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
        } catch (\Throwable $e) {
            Log::warning('whatsapp.auto_tags_failed', [
                'conversation_id' => $conversation->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<int, string>  $manualTags
     */
    public function syncManualTags(WaConversation $conversation, array $manualTags): void
    {
        if (! Schema::hasTable('wa_conversation_tags')) {
            return;
        }

        $manualTags = array_values(array_unique(array_filter(
            $manualTags,
            fn ($t) => WhatsappConversationTagCatalog::isValid((string) $t)
        )));

        $autoTags = WaConversationTag::query()
            ->where('conversation_id', $conversation->id)
            ->where('auto_applied', true)
            ->pluck('tag')
            ->all();

        WaConversationTag::query()
            ->where('conversation_id', $conversation->id)
            ->where('auto_applied', false)
            ->whereNotIn('tag', $manualTags)
            ->delete();

        foreach ($manualTags as $tag) {
            if (in_array($tag, $autoTags, true)) {
                continue;
            }
            WaConversationTag::query()->firstOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'tag' => $tag,
                ],
                ['auto_applied' => false]
            );
        }
    }

    /**
     * @return Collection<int, string>
     */
    public function tagsForConversation(int $conversationId): Collection
    {
        if (! Schema::hasTable('wa_conversation_tags')) {
            return collect();
        }

        return WaConversationTag::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('tag')
            ->pluck('tag');
    }

    /**
     * @return array<int, string>
     */
    private function tagsFromRecentInboundText(WaConversation $conversation): array
    {
        $text = $conversation->messages()
            ->where('direction', 'in')
            ->orderByDesc('created_at')
            ->limit(5)
            ->pluck('body')
            ->filter()
            ->implode(' ');

        $haystack = strtolower($text);
        if ($haystack === '') {
            return [];
        }

        $found = [];
        if (str_contains($haystack, 'renew')) {
            $found[] = 'renewal_due';
        }
        if (preg_match('/\b(rent|payment|pay)\b/', $haystack)) {
            $found[] = 'payment_pending';
        }
        if (preg_match('/\b(verify|verification|kyc)\b/', $haystack)) {
            $found[] = 'verification';
        }
        if (preg_match('/\b(maintenance|repair|leak|plumb)\b/', $haystack)) {
            $found[] = 'maintenance';
        }
        if (preg_match('/\b(urgent|asap|hot)\b/', $haystack)) {
            $found[] = 'hot_lead';
        }

        return $found;
    }
}
