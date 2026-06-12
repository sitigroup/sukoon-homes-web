<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvAutomationRun;
use App\Plugins\TrustVerification\Models\TvCheckItem;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\Automation\HttpVerificationProvider;
use App\Plugins\TrustVerification\Services\Automation\ManualVerificationProvider;
use App\Plugins\TrustVerification\Services\Automation\RulesVerificationProvider;
use App\Plugins\TrustVerification\Services\Automation\VerificationProviderInterface;
use Throwable;

class TrustVerificationAutomationService
{
    /** @return array<string, string> */
    public static function providerOptions(): array
    {
        $options = [];
        foreach (self::providers() as $provider) {
            $options[$provider->key()] = $provider->label();
        }

        return $options;
    }

    /** @return list<VerificationProviderInterface> */
    public static function providers(): array
    {
        return [
            new ManualVerificationProvider,
            new RulesVerificationProvider,
            new HttpVerificationProvider,
        ];
    }

    public static function provider(string $key): VerificationProviderInterface
    {
        foreach (self::providers() as $provider) {
            if ($provider->key() === $key) {
                return $provider;
            }
        }

        return new RulesVerificationProvider;
    }

    public static function runForOrder(TvOrder $order, string $trigger = 'manual', ?string $providerKey = null): TvAutomationRun
    {
        $settings = TrustVerificationSettingsService::all();

        if (! ($settings['automation_enabled'] ?? false)) {
            throw new \RuntimeException('Automation is disabled in settings.');
        }

        $providerKey = $providerKey ?: (string) ($settings['automation_provider'] ?? 'rules');
        if ($providerKey === 'http' && ! TrustVerificationSettingsService::httpProviderConfigured($settings)) {
            $providerKey = 'rules';
        }

        $provider = self::provider($providerKey);

        $run = TvAutomationRun::create([
            'order_id' => $order->id,
            'provider' => $provider->key(),
            'status' => 'running',
            'trigger' => $trigger,
            'started_at' => now(),
        ]);

        $order->update(['automation_status' => 'running']);

        try {
            $order->loadMissing(['subject', 'checkItems', 'documents', 'package']);
            $requestPayload = [
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'city_slug' => $order->city_slug,
            ];
            $run->update(['request_payload' => $requestPayload]);

            $results = $provider->run($order, $settings);
            $updated = 0;

            if (($settings['automation_auto_apply'] ?? true) && $results !== []) {
                $updated = self::applyResults($order, $results);
            }

            $run->update([
                'status' => 'completed',
                'response_payload' => ['results' => $results, 'checks_updated' => $updated],
                'checks_updated' => $updated,
                'completed_at' => now(),
            ]);

            $order->update(['automation_status' => self::resolveOrderAutomationStatus($order->fresh(['checkItems']))]);

            if ($order->status === 'submitted' && $updated > 0) {
                $order->update(['status' => 'in_progress']);
            }

            return $run->fresh();
        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            $order->update(['automation_status' => 'failed']);

            throw $e;
        }
    }

    /**
     * @param  array<int, array{check_key: string, status: string, notes?: ?string}>  $results
     */
    public static function applyResults(TvOrder $order, array $results): int
    {
        $order->loadMissing('checkItems');
        $updated = 0;

        foreach ($results as $row) {
            $item = $order->checkItems->firstWhere('check_key', $row['check_key'] ?? '');
            if (! $item || $item->status === 'na') {
                continue;
            }

            $status = strtolower((string) ($row['status'] ?? ''));
            if (! in_array($status, ['pending', 'pass', 'fail'], true)) {
                continue;
            }

            $item->status = $status;
            if (! empty($row['notes'])) {
                $item->notes = (string) $row['notes'];
            }
            $item->completed_at = in_array($status, ['pass', 'fail'], true) ? now() : null;
            $item->save();
            $updated++;
        }

        return $updated;
    }

    /** @param array<int, array{check_key: string, status: string, notes?: ?string}> $results */
    public static function applyWebhookResults(TvOrder $order, array $results): TvAutomationRun
    {
        $run = TvAutomationRun::create([
            'order_id' => $order->id,
            'provider' => 'webhook',
            'status' => 'running',
            'trigger' => 'webhook',
            'request_payload' => ['results_count' => count($results)],
            'started_at' => now(),
        ]);

        try {
            $updated = self::applyResults($order, $results);
            $run->update([
                'status' => 'completed',
                'response_payload' => ['results' => $results, 'checks_updated' => $updated],
                'checks_updated' => $updated,
                'completed_at' => now(),
            ]);
            $order->update(['automation_status' => self::resolveOrderAutomationStatus($order->fresh(['checkItems']))]);

            return $run->fresh();
        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            throw $e;
        }
    }

    public static function maybeRunOnPaid(TvOrder $order): void
    {
        $settings = TrustVerificationSettingsService::all();
        if (! ($settings['automation_enabled'] ?? false) || ! ($settings['automation_on_paid'] ?? false)) {
            return;
        }

        if (! in_array($order->payment_status, ['paid', 'waived'], true)) {
            return;
        }

        if (! in_array($order->status, ['submitted', 'in_progress'], true)) {
            return;
        }

        try {
            self::runForOrder($order, 'payment_paid');
        } catch (Throwable) {
            // Ops can retry from admin; do not block payment flow.
        }
    }

    public static function resolveOrderAutomationStatus(TvOrder $order): string
    {
        $order->loadMissing('checkItems');
        $active = $order->checkItems->where('status', '!=', 'na');

        if ($active->isEmpty()) {
            return 'completed';
        }

        if ($active->every(fn (TvCheckItem $i) => in_array($i->status, ['pass', 'fail'], true))) {
            return 'completed';
        }

        if ($active->contains(fn (TvCheckItem $i) => in_array($i->status, ['pass', 'fail'], true))) {
            return 'partial';
        }

        return 'queued';
    }

    public static function generateRiskSummary(TvOrder $order): string
    {
        $suggestion = TrustVerificationRiskService::suggestRiskLevel($order);
        $order->loadMissing(['subject', 'checkItems']);

        $lines = [
            'Verification summary for '.$order->subject?->full_name,
            'Order: '.$order->order_number.' · '.ucfirst($order->order_type).' · '.TrustVerificationService::cityLabel($order->city_slug),
        ];

        if ($suggestion['level']) {
            $lines[] = 'Suggested risk: '.strtoupper($suggestion['level']).' — '.$suggestion['reason'];
        }

        foreach ($order->checkItems->where('status', '!=', 'na') as $item) {
            $lines[] = '- '.$item->label.': '.$item->status.($item->notes ? ' ('.$item->notes.')' : '');
        }

        $policeReport = TrustVerificationPoliceVerificationService::formatForReport($order);
        if ($policeReport) {
            $lines[] = '';
            $lines[] = 'Police Verification';
            $lines[] = 'Status: '.($policeReport['status_label'] ?? $policeReport['status']);
            if ($policeReport['reference_number']) {
                $lines[] = 'Reference No: '.$policeReport['reference_number'];
            }
            if ($policeReport['police_station_name']) {
                $lines[] = 'Police Station: '.$policeReport['police_station_name'];
            }
            if ($policeReport['completed_at']) {
                $lines[] = 'Completed Date: '.TrustVerificationOrderTimestampsService::formatDisplay(
                    \Carbon\Carbon::parse($policeReport['completed_at'])
                );
            }
            $lines[] = 'Note: '.$policeReport['disclaimer'];
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $subjectPayload */
    public static function applyInitialIdValidation(TvOrder $order, array $subjectPayload): void
    {
        $idNumber = trim((string) ($subjectPayload['id_number'] ?? ''));
        $idType = (string) ($subjectPayload['id_type'] ?? '');
        if ($idNumber === '') {
            return;
        }

        $provider = new \App\Plugins\TrustVerification\Services\Automation\RulesVerificationProvider;
        $order->loadMissing('checkItems');
        $fakeSubject = (object) [
            'id_type' => $idType,
            'id_number_hint' => $idNumber,
        ];

        $item = $order->checkItems->firstWhere('check_key', 'id_verification');
        if (! $item || $item->status === 'na') {
            return;
        }

        $digits = preg_replace('/\D/', '', $idNumber);
        $status = 'pending';
        $notes = 'ID submitted — pending document verification.';

        if (stripos($idType, 'aadhaar') !== false && strlen($digits) === 12) {
            $status = 'pass';
            $notes = 'Aadhaar format valid at submit. Verify uploaded document.';
        } elseif (stripos($idType, 'pan') !== false && preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/i', strtoupper(preg_replace('/\s+/', '', $idNumber)))) {
            $status = 'pass';
            $notes = 'PAN format valid at submit. Verify uploaded document.';
        } elseif (stripos($idType, 'aadhaar') !== false) {
            $status = 'fail';
            $notes = 'Aadhaar number format invalid at submit.';
        }

        $item->update([
            'status' => $status,
            'notes' => $notes,
            'completed_at' => in_array($status, ['pass', 'fail'], true) ? now() : null,
        ]);
    }
}
