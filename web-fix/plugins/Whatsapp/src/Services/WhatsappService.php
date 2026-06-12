<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Jobs\SendWhatsappTemplateJob;
use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsappService
{
    /**
     * @return array{status:string,badge:string,message:string,reason?:string}
     */
    public function notify(string $eventKey, array $contact, array $vars = []): array
    {
        try {
            $this->ensureWaContactLinked($contact);

            $map = WaEventMap::query()->where('event_key', $eventKey)->first();
            if (! $map) {
                $this->safeAudit('event_skipped', [
                    'event_key' => $eventKey,
                    'reason' => 'unmapped',
                    'contact' => $contact,
                ]);
                return $this->result('skipped', 'secondary', 'Skipped - event not mapped', 'unmapped');
            }

            if (! $map->enabled) {
                $this->safeAudit('event_skipped', [
                    'event_key' => $eventKey,
                    'reason' => 'event_disabled',
                    'contact' => $contact,
                ]);
                return $this->result('skipped', 'secondary', 'Skipped - event disabled', 'event_disabled');
            }

            if (! $map->template_id) {
                $this->safeAudit('event_skipped', [
                    'event_key' => $eventKey,
                    'reason' => 'template_unmapped',
                    'contact' => $contact,
                ]);
                return $this->result('skipped', 'secondary', 'Skipped - no template mapped', 'template_unmapped');
            }

            $template = WaTemplate::query()->find($map->template_id);
            if (! $template) {
                $this->safeAudit('event_skipped', [
                    'event_key' => $eventKey,
                    'reason' => 'template_not_found',
                    'map_id' => $map->id,
                ]);
                return $this->result('skipped', 'secondary', 'Skipped - template not found', 'template_not_found');
            }

            if (strtolower((string) $template->status) !== 'approved' || ! $template->enabled) {
                $reason = strtolower((string) $template->status) !== 'approved'
                    ? 'template_pending_meta_approval'
                    : 'template_disabled';
                $this->safeAudit('event_skipped', [
                    'event_key' => $eventKey,
                    'reason' => $reason,
                    'template_id' => $template->id,
                    'template_status' => $template->status,
                ]);
                if ($reason === 'template_pending_meta_approval') {
                    return $this->result('skipped', 'warning', 'Skipped - template pending Meta approval', $reason);
                }
                return $this->result('skipped', 'secondary', 'Skipped - template disabled', $reason);
            }

            SendWhatsappTemplateJob::dispatch($contact, $eventKey, $template->id, $vars)->onQueue('whatsapp');
            $this->safeAudit('event_queued', [
                'event_key' => $eventKey,
                'template_id' => $template->id,
                'contact' => $contact,
            ]);
            return $this->result('sent', 'success', 'Sent', 'queued');
        } catch (\Throwable $e) {
            report($e);
            $this->safeAudit('send_failed', [
                'event_key' => $eventKey,
                'contact' => $contact,
                'error' => $e->getMessage(),
            ]);
            return $this->result('failed', 'danger', 'Failed - ' . $e->getMessage(), 'exception');
        }
    }

    private function safeAudit(string $event, array $meta): void
    {
        try {
            DB::table('wa_audit_log')->insert([
                'event' => $event,
                'entity_type' => 'event',
                'meta_json' => json_encode($meta),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // never block platform flow
        }
    }

    private function ensureWaContactLinked(array $contact): void
    {
        if (! Schema::hasTable('wa_contacts')) {
            return;
        }

        $phone = preg_replace('/\D+/', '', (string) ($contact['phone'] ?? ''));
        if ($phone === '') {
            return;
        }

        $existing = DB::table('wa_contacts')->where('phone', $phone)->first();
        $payload = [
            'customer_type' => $contact['customer_type'] ?? null,
            'customer_id' => $contact['customer_id'] ?? null,
            'opted_in' => 1,
            'last_message_at' => now(),
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('wa_contacts')->where('id', $existing->id)->update($payload);
            return;
        }

        DB::table('wa_contacts')->insert(array_merge($payload, [
            'phone' => $phone,
            'created_at' => now(),
        ]));
    }

    /**
     * @return array{status:string,badge:string,message:string,reason?:string}
     */
    private function result(string $status, string $badge, string $message, string $reason = ''): array
    {
        $data = [
            'status' => $status,
            'badge' => $badge,
            'message' => $message,
        ];

        if ($reason !== '') {
            $data['reason'] = $reason;
        }

        return $data;
    }
}

