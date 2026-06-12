<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WaContact;
use App\Plugins\Whatsapp\Models\WaConversation;
use App\Plugins\Whatsapp\Models\WaMessage;
use App\Plugins\Whatsapp\Models\WaSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsappOffHoursService
{
    public function isBusinessHours(?WaSetting $settings = null): bool
    {
        $settings = $settings ?? WaSetting::query()->latest('id')->first();
        if (! $settings || ! $settings->off_hours_enabled) {
            return true;
        }

        $tz = $this->timezone($settings);
        $now = Carbon::now($tz);
        $start = $this->timeOnDate($now, (string) $settings->business_hours_start, $tz);
        $end = $this->timeOnDate($now, (string) $settings->business_hours_end, $tz);

        if ($end->lessThanOrEqual($start)) {
            return $now->greaterThanOrEqual($start) || $now->lessThan($end);
        }

        return $now->betweenIncluded($start, $end);
    }

    public function maybeAutoReply(WaConversation $conversation, WaContact $contact): bool
    {
        try {
            $settings = WaSetting::query()->latest('id')->first();
            if (! $settings?->off_hours_enabled) {
                return false;
            }

            $text = trim((string) ($settings->off_hours_reply_text ?? ''));
            if ($text === '') {
                return false;
            }

            if ($this->isBusinessHours($settings)) {
                return false;
            }

            if (! $this->shouldSendToday($conversation, $settings)) {
                return false;
            }

            $phone = (string) ($contact->phone ?? '');
            if ($phone === '') {
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
                'error_json' => $status === 'failed' ? ($result['json'] ?? ['error' => 'off_hours_send_failed']) : null,
            ]);

            $conversation->off_hours_auto_reply_at = now();
            if ($conversation->status === 'open') {
                $conversation->status = 'pending';
            }
            $conversation->last_message_at = now();
            $conversation->save();

            DB::table('wa_audit_log')->insert([
                'event' => 'off_hours_auto_reply',
                'entity_type' => 'conversation',
                'entity_id' => $conversation->id,
                'meta_json' => json_encode(['status' => $status, 'phone' => substr($phone, 0, 4).'****']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $status === 'sent';
        } catch (\Throwable $e) {
            Log::warning('whatsapp.off_hours_auto_reply_failed', [
                'conversation_id' => $conversation->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function shouldSendToday(WaConversation $conversation, WaSetting $settings): bool
    {
        if (! $conversation->off_hours_auto_reply_at) {
            return true;
        }

        $tz = $this->timezone($settings);
        $today = Carbon::now($tz)->toDateString();
        $lastDay = Carbon::parse($conversation->off_hours_auto_reply_at)->timezone($tz)->toDateString();

        return $lastDay !== $today;
    }

    private function timezone(WaSetting $settings): string
    {
        $tz = trim((string) ($settings->business_timezone ?? 'Asia/Kolkata'));

        return $tz !== '' ? $tz : 'Asia/Kolkata';
    }

    private function timeOnDate(Carbon $date, string $hhmm, string $tz): Carbon
    {
        $parts = explode(':', $hhmm);
        $hour = (int) ($parts[0] ?? 9);
        $minute = (int) ($parts[1] ?? 0);

        return $date->copy()->timezone($tz)->setTime($hour, $minute, 0);
    }
}
