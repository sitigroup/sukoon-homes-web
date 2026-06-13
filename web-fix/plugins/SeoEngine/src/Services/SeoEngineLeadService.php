<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\SeoEngine\Models\SeoEngineLead;
use Illuminate\Support\Facades\Schema;

class SeoEngineLeadService
{
    public function __construct(
        private SeoEngineSettingsService $settings
    ) {
    }

    /**
     * @param  array{name:string,phone:string,requirement?:string,source_path:string,area_id?:int|null,sub_area_id?:int|null,form_type?:string,ip?:string}  $input
     */
    public function store(array $input): SeoEngineLead
    {
        $lead = SeoEngineLead::query()->create([
            'name' => $input['name'],
            'phone' => $input['phone'],
            'requirement' => $input['requirement'] ?? null,
            'source_path' => $input['source_path'],
            'area_id' => $input['area_id'] ?? null,
            'sub_area_id' => $input['sub_area_id'] ?? null,
            'form_type' => $input['form_type'] ?? 'lead',
            'status' => 'new',
            'ip_hash' => isset($input['ip']) ? hash('sha256', (string) $input['ip']) : null,
        ]);

        $this->notifyTeam($lead);
        $this->notifyRenter($lead);

        return $lead;
    }

    private function notifyTeam(SeoEngineLead $lead): void
    {
        if (! class_exists(\App\Plugins\Whatsapp\Services\WhatsappService::class)) {
            return;
        }

        $teamPhone = trim((string) $this->settings->get('lead_notify_phone', ''));
        if ($teamPhone === '') {
            return;
        }

        try {
            app(\App\Plugins\Whatsapp\Services\WhatsappService::class)->notify('seo_engine_lead', [
                'phone' => $teamPhone,
                'name' => 'SEO Lead',
            ], [
                'lead_name' => $lead->name,
                'lead_phone' => $lead->phone,
                'requirement' => $lead->requirement ?: '—',
                'source_path' => $lead->source_path,
                'form_type' => $lead->form_type,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function notifyRenter(SeoEngineLead $lead): void
    {
        if (! class_exists(\App\Plugins\Whatsapp\Services\WhatsappService::class)) {
            return;
        }

        try {
            app(\App\Plugins\Whatsapp\Services\WhatsappService::class)->notify('seo_engine_lead_auto_reply', [
                'phone' => $lead->phone,
                'name' => $lead->name,
            ], [
                'lead_name' => $lead->name,
                'source_path' => $lead->source_path,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array{new:int,contacted:int,closed:int,total:int}
     */
    public function statusCounts(): array
    {
        if (! Schema::hasTable('seo_engine_leads')) {
            return ['new' => 0, 'contacted' => 0, 'closed' => 0, 'total' => 0];
        }

        $rows = SeoEngineLead::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return [
            'new' => (int) ($rows['new'] ?? 0),
            'contacted' => (int) ($rows['contacted'] ?? 0),
            'closed' => (int) ($rows['closed'] ?? 0),
            'total' => (int) SeoEngineLead::query()->count(),
        ];
    }
}
