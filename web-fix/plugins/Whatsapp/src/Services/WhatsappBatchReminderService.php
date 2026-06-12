<?php

namespace App\Plugins\Whatsapp\Services;

use App\Models\Customer;
use App\Plugins\Whatsapp\Models\WaBatchReminderLog;
use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaTemplate;
use App\Plugins\Whatsapp\Support\WhatsappPhoneHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WhatsappBatchReminderService
{
    /**
     * @return array{rent:bool,renewal:bool}
     */
    public function availability(): array
    {
        return [
            'rent' => class_exists(\App\Plugins\OwnerDashboard\Models\OwnerTenancy::class),
            'renewal' => class_exists(\App\Plugins\RenewalReminder\Services\RenewalReminderService::class),
        ];
    }

    /**
     * @return array{eligible:int,skipped:int,rows:array<int,array<string,mixed>>,due_date:string}
     */
    public function previewRent(string $dueDate): array
    {
        $rows = $this->buildRentRows($dueDate);

        return [
            'due_date' => $dueDate,
            'eligible' => $rows->where('status', 'eligible')->count(),
            'skipped' => $rows->where('status', 'skipped')->count(),
            'rows' => $rows->values()->all(),
        ];
    }

    /**
     * @return array{batch_id:string,sent:int,skipped:int,failed:int,rows:array<int,array<string,mixed>>}
     */
    public function runRent(string $dueDate, ?int $actorId): array
    {
        $batchId = (string) Str::uuid();
        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $resultRows = [];

        foreach ($this->buildRentRows($dueDate) as $row) {
            if ($row['status'] !== 'eligible') {
                $skipped++;
                $this->logRow($batchId, 'rent', $actorId, 'rent_reminder', 'owner_tenancy', $row, 'skipped', $row['skip_reason'] ?? null);
                $resultRows[] = array_merge($row, ['result_status' => 'skipped']);

                continue;
            }

            $result = \Whatsapp::notify('rent_reminder', [
                'phone' => $row['phone'],
                'customer_type' => 'tenant',
                'customer_id' => $row['customer_id'],
            ], [
                (string) $row['rent_amount'],
                (string) $row['property_label'],
                $dueDate,
            ]);

            $status = (string) ($result['status'] ?? 'failed');
            if ($status === 'sent') {
                $sent++;
                $logStatus = 'queued';
            } elseif ($status === 'skipped') {
                $skipped++;
                $logStatus = 'skipped';
            } else {
                $failed++;
                $logStatus = 'failed';
            }

            $this->logRow(
                $batchId,
                'rent',
                $actorId,
                'rent_reminder',
                'owner_tenancy',
                $row,
                $logStatus,
                $result['reason'] ?? null,
                $result
            );

            $resultRows[] = array_merge($row, [
                'result_status' => $logStatus,
                'result_message' => $result['message'] ?? '',
            ]);
        }

        return [
            'batch_id' => $batchId,
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'rows' => $resultRows,
        ];
    }

    /**
     * @return array{eligible:int,skipped:int,rows:array<int,array<string,mixed>>,whatsapp_enabled:bool,reminder_days:array<int,int>}
     */
    public function previewRenewal(): array
    {
        if (! class_exists(\App\Plugins\RenewalReminder\Models\RenewalSettings::class)) {
            return [
                'eligible' => 0,
                'skipped' => 0,
                'rows' => [],
                'whatsapp_enabled' => false,
                'reminder_days' => [],
            ];
        }

        $settings = \App\Plugins\RenewalReminder\Models\RenewalSettings::current();
        $rows = $this->buildRenewalRows($settings);

        return [
            'eligible' => $rows->where('status', 'eligible')->count(),
            'skipped' => $rows->where('status', 'skipped')->count(),
            'rows' => $rows->values()->all(),
            'whatsapp_enabled' => (bool) $settings->send_whatsapp,
            'reminder_days' => array_map('intval', (array) $settings->reminder_days),
        ];
    }

    /**
     * Runs the existing Renewal Reminder daily check (email/push/WhatsApp per plugin settings).
     *
     * @return array{batch_id:string,reminders_sent:int,skipped:int,preview_eligible:int,whatsapp_enabled:bool,note:string}
     */
    public function runRenewal(?int $actorId): array
    {
        $preview = $this->previewRenewal();
        $batchId = (string) Str::uuid();

        $report = ['reminders_sent' => 0, 'skipped' => 0];
        if (class_exists(\App\Plugins\RenewalReminder\Services\RenewalReminderService::class)) {
            $report = app(\App\Plugins\RenewalReminder\Services\RenewalReminderService::class)->runDailyCheck();
        }

        foreach ($preview['rows'] as $row) {
            if ($row['status'] !== 'eligible') {
                $this->logRow($batchId, 'renewal', $actorId, 'agreement_renewal', 'rental_agreement', $row, 'skipped', $row['skip_reason'] ?? null);

                continue;
            }

            $this->logRow(
                $batchId,
                'renewal',
                $actorId,
                'agreement_renewal',
                'rental_agreement',
                $row,
                $preview['whatsapp_enabled'] ? 'queued' : 'skipped',
                $preview['whatsapp_enabled'] ? 'renewal_check_ran' : 'whatsapp_disabled_in_renewal_settings',
                ['renewal_check' => true]
            );
        }

        return [
            'batch_id' => $batchId,
            'reminders_sent' => (int) ($report['reminders_sent'] ?? 0),
            'skipped' => (int) ($report['skipped'] ?? 0),
            'preview_eligible' => (int) $preview['eligible'],
            'whatsapp_enabled' => (bool) $preview['whatsapp_enabled'],
            'note' => __('whatsapp::whatsapp.batch_renewal_note'),
        ];
    }

    /**
     * @return Collection<int, WaBatchReminderLog>
     */
    public function recentBatches(int $limit = 10): Collection
    {
        if (! Schema::hasTable('wa_batch_reminder_logs')) {
            return collect();
        }

        $batchIds = WaBatchReminderLog::query()
            ->select('batch_id')
            ->selectRaw('MAX(created_at) as last_at')
            ->groupBy('batch_id')
            ->orderByDesc('last_at')
            ->limit($limit)
            ->pluck('batch_id');

        if ($batchIds->isEmpty()) {
            return collect();
        }

        $rows = WaBatchReminderLog::query()
            ->whereIn('batch_id', $batchIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('batch_id');

        return $batchIds->map(function (string $batchId) use ($rows) {
            $items = $rows->get($batchId, collect());
            $first = $items->first();

            return (object) [
                'batch_id' => $batchId,
                'batch_type' => $first?->batch_type ?? '',
                'created_at' => $first?->created_at,
                'sent' => $items->where('status', 'queued')->count(),
                'skipped' => $items->where('status', 'skipped')->count(),
                'failed' => $items->where('status', 'failed')->count(),
                'total' => $items->count(),
            ];
        });
    }

    /**
     * @return Collection<int, WaBatchReminderLog>
     */
    public function batchRows(string $batchId): Collection
    {
        if (! Schema::hasTable('wa_batch_reminder_logs')) {
            return collect();
        }

        return WaBatchReminderLog::query()
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildRentRows(string $dueDate): Collection
    {
        if (! class_exists(\App\Plugins\OwnerDashboard\Models\OwnerTenancy::class)) {
            return collect();
        }

        $tenancyClass = \App\Plugins\OwnerDashboard\Models\OwnerTenancy::class;
        $eventReady = $this->eventReady('rent_reminder');

        return $tenancyClass::query()
            ->with(['tenant', 'property', 'agreement'])
            ->whereIn('status', $tenancyClass::VISIBLE_STATUSES)
            ->orderBy('id')
            ->get()
            ->map(function ($tenancy) use ($dueDate, $eventReady) {
                $phone = WhatsappPhoneHelper::toMetaRecipient($this->resolveTenantPhone($tenancy));
                $skipReasons = [];

                if ($phone === '') {
                    $skipReasons[] = 'no_phone';
                }
                if (! $eventReady) {
                    $skipReasons[] = 'event_not_ready';
                }

                return [
                    'recipient_id' => (int) $tenancy->id,
                    'customer_id' => (int) ($tenancy->tenant_customer_id ?? 0) ?: null,
                    'label' => (string) ($tenancy->tenant_display_name ?? $tenancy->tenant?->name ?? ('Tenancy #' . $tenancy->id)),
                    'property_label' => (string) ($tenancy->property?->title ?? ('#' . $tenancy->property_id)),
                    'rent_amount' => (string) ($tenancy->monthly_rent ?? 0),
                    'phone' => $phone,
                    'due_date' => $dueDate,
                    'status' => empty($skipReasons) ? 'eligible' : 'skipped',
                    'skip_reason' => implode(', ', $skipReasons),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildRenewalRows(object $settings): Collection
    {
        if (! class_exists(\App\Plugins\RentalAgreement\Models\RentalAgreement::class)) {
            return collect();
        }

        $agreementClass = \App\Plugins\RentalAgreement\Models\RentalAgreement::class;
        $logClass = \App\Plugins\RenewalReminder\Models\RenewalReminderLog::class;
        $eventReady = $this->eventReady('agreement_renewal');
        $rows = collect();

        foreach ((array) $settings->reminder_days as $days) {
            $days = (int) $days;
            $targetDate = now()->addDays($days)->toDateString();

            $agreementClass::query()
                ->whereIn('status', ['generated', 'signed_copy_uploaded'])
                ->whereDate('end_date', $targetDate)
                ->orderBy('id')
                ->each(function ($agreement) use ($days, $logClass, $eventReady, &$rows) {
                    $alreadySent = $logClass::query()
                        ->where('agreement_id', $agreement->id)
                        ->where('days_before_end', $days)
                        ->exists();

                    $phone = WhatsappPhoneHelper::toMetaRecipient($this->resolveAgreementTenantPhone($agreement));
                    $skipReasons = [];

                    if ($alreadySent) {
                        $skipReasons[] = 'already_sent';
                    }
                    if ($days === 15) {
                        $skipReasons[] = 'admin_only_interval';
                    }
                    if ((int) ($agreement->customer_id ?? 0) <= 0) {
                        $skipReasons[] = 'no_customer';
                    }
                    if ($phone === '') {
                        $skipReasons[] = 'no_phone';
                    }
                    if (! $eventReady) {
                        $skipReasons[] = 'event_not_ready';
                    }

                    $rows->push([
                        'recipient_id' => (int) $agreement->id,
                        'customer_id' => (int) ($agreement->customer_id ?? 0) ?: null,
                        'label' => (string) ($agreement->tenant_name ?? $agreement->agreement_number ?? ('RA-' . $agreement->id)),
                        'agreement_number' => (string) ($agreement->agreement_number ?? ''),
                        'end_date' => $agreement->end_date?->format('d M Y') ?? '',
                        'days_before_end' => $days,
                        'phone' => $phone,
                        'status' => empty($skipReasons) ? 'eligible' : 'skipped',
                        'skip_reason' => implode(', ', $skipReasons),
                    ]);
                });
        }

        return $rows;
    }

    private function eventReady(string $eventKey): bool
    {
        $map = WaEventMap::query()->where('event_key', $eventKey)->first();
        if (! $map || ! $map->enabled || ! $map->template_id) {
            return false;
        }

        $template = WaTemplate::query()->find($map->template_id);
        if (! $template || ! $template->enabled) {
            return false;
        }

        return strtolower((string) $template->status) === 'approved';
    }

    private function resolveTenantPhone(object $tenancy): string
    {
        $phone = preg_replace('/\D+/', '', (string) ($tenancy->agreement?->tenant_phone ?? ''));
        if ($phone !== '') {
            return $phone;
        }

        $tenant = $tenancy->tenant ?? null;
        if (! $tenant && (int) ($tenancy->tenant_customer_id ?? 0) > 0 && class_exists(Customer::class)) {
            $tenant = Customer::query()->find((int) $tenancy->tenant_customer_id);
        }

        return preg_replace('/\D+/', '', (string) ($tenant?->mobile ?? $tenant?->phone ?? ''));
    }

    private function resolveAgreementTenantPhone(object $agreement): string
    {
        $phone = preg_replace('/\D+/', '', (string) ($agreement->tenant_phone ?? ''));
        if ($phone !== '') {
            return $phone;
        }

        $customerId = (int) ($agreement->customer_id ?? 0);
        if ($customerId > 0 && class_exists(Customer::class)) {
            $customer = Customer::query()->find($customerId);

            return preg_replace('/\D+/', '', (string) ($customer?->mobile ?? $customer?->phone ?? ''));
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>|null  $result
     */
    private function logRow(
        string $batchId,
        string $batchType,
        ?int $actorId,
        string $eventKey,
        string $recipientType,
        array $row,
        string $status,
        ?string $skipReason = null,
        ?array $result = null
    ): void {
        if (! Schema::hasTable('wa_batch_reminder_logs')) {
            return;
        }

        WaBatchReminderLog::query()->create([
            'batch_id' => $batchId,
            'batch_type' => $batchType,
            'actor_id' => $actorId,
            'event_key' => $eventKey,
            'recipient_type' => $recipientType,
            'recipient_id' => $row['recipient_id'] ?? null,
            'phone' => $row['phone'] ?? null,
            'label' => $row['label'] ?? null,
            'status' => $status,
            'skip_reason' => $skipReason,
            'result_json' => $result,
        ]);
    }
}
