<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WaMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsappWebhookHealthService
{
    /**
     * @return array{
     *   webhook_url:string,
     *   last_webhook_at:?string,
     *   inbound_24h:int,
     *   pending_jobs:int,
     *   failed_jobs_24h:int,
     *   is_recent_webhook:bool
     * }
     */
    public function snapshot(): array
    {
        $lastWebhook = null;
        $pendingJobs = 0;
        $failedJobsRecent = 0;

        if (Schema::hasTable('wa_audit_log')) {
            $lastWebhook = DB::table('wa_audit_log')
                ->where('event', 'webhook_received')
                ->max('created_at');
        }

        if (Schema::hasTable('jobs')) {
            $pendingJobs = (int) DB::table('jobs')->where('queue', 'whatsapp')->count();
        }

        if (Schema::hasTable('failed_jobs')) {
            $failedJobsRecent = (int) DB::table('failed_jobs')
                ->where('queue', 'whatsapp')
                ->where('failed_at', '>=', now()->subDay())
                ->count();
        }

        $inbound24h = (int) WaMessage::query()
            ->where('direction', 'in')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $isRecent = false;
        if ($lastWebhook) {
            try {
                $isRecent = now()->parse($lastWebhook)->gte(now()->subHours(6));
            } catch (\Throwable) {
                $isRecent = false;
            }
        }

        return [
            'webhook_url' => url('/api/whatsapp/webhook'),
            'last_webhook_at' => $lastWebhook,
            'inbound_24h' => $inbound24h,
            'pending_jobs' => $pendingJobs,
            'failed_jobs_24h' => $failedJobsRecent,
            'is_recent_webhook' => $isRecent,
        ];
    }
}
