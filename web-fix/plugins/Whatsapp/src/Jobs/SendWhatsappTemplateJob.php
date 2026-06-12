<?php

namespace App\Plugins\Whatsapp\Jobs;

use App\Plugins\Whatsapp\Models\WaContact;
use App\Plugins\Whatsapp\Models\WaConversation;
use App\Plugins\Whatsapp\Models\WaMessage;
use App\Plugins\Whatsapp\Models\WaTemplate;
use App\Plugins\Whatsapp\Services\MetaGraphClient;
use App\Plugins\Whatsapp\Support\WaTemplateVariableHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendWhatsappTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 90];

    public function __construct(
        public array $contact,
        public string $eventKey,
        public int $templateId,
        public array $variables = []
    ) {
        $this->queue = 'whatsapp';
    }

    public function handle(MetaGraphClient $meta): void
    {
        $phone = $this->contact['phone'] ?? null;
        if (! $phone) {
            return;
        }

        $template = WaTemplate::query()
            ->where('id', $this->templateId)
            ->where('enabled', true)
            ->whereIn('category', ['UTILITY', 'AUTHENTICATION', 'Utility', 'Authentication'])
            ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['approved'])
            ->orderByDesc('id')
            ->first();

        if (! $template) {
            DB::table('wa_audit_log')->insert([
                'event' => 'template_missing',
                'entity_type' => 'template',
                'meta_json' => json_encode([
                    'event_key' => $this->eventKey,
                    'template_id' => $this->templateId,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        $contact = WaContact::query()->firstOrCreate(['phone' => $phone], [
            'customer_type' => $this->contact['customer_type'] ?? null,
            'customer_id' => $this->contact['customer_id'] ?? null,
            'opted_in' => true,
        ]);
        $conversation = WaConversation::query()->firstOrCreate(['contact_id' => $contact->id]);

        $variables = WaTemplateVariableHelper::normalizeForTemplate($template, $this->variables);
        $send = $meta->sendTemplate($phone, $template->meta_template_name, $template->language ?? 'en', $variables);
        $wamid = data_get($send, 'json.messages.0.id');
        $status = $send['ok'] ? 'sent' : 'failed';

        $message = WaMessage::query()->create([
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'wamid' => $wamid,
            'direction' => 'out',
            'type' => 'template',
            'template_key' => $this->eventKey,
            'body' => json_encode($variables),
            'status' => $status,
            'error_json' => $send['ok'] ? null : ($send['json'] ?? ['error' => $send['error'] ?? 'send_failed']),
        ]);

        DB::table('wa_audit_log')->insert([
            'event' => 'template_sent',
            'entity_type' => 'message',
            'entity_id' => $message->id,
            'meta_json' => json_encode([
                'event_key' => $this->eventKey,
                'template_id' => $template->id,
                'phone' => $phone,
                'status' => $status,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

