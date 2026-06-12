<?php

namespace App\Plugins\RenewalReminder\Services;

use App\Models\Usertokens;
use App\Plugins\RenewalReminder\Models\RenewalIntention;
use App\Plugins\RenewalReminder\Models\RenewalReminderLog;
use App\Plugins\RenewalReminder\Models\RenewalSettings;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use App\Plugins\RentalAgreement\Services\RentalAgreementNumberService;
use App\Plugins\RentalAgreement\Models\RentalAgreementSelectedClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RenewalReminderService
{
    public function __construct(
        private readonly RentalAgreementNumberService $numberService,
    ) {}

    // ── Run daily reminder check ───────────────────────────────────────

    public function runDailyCheck(): array
    {
        $settings = RenewalSettings::current();
        $report   = ['reminders_sent' => 0, 'skipped' => 0];

        foreach ($settings->reminder_days as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            RentalAgreement::whereIn('status', ['generated', 'signed_copy_uploaded'])
                ->whereDate('end_date', $targetDate)
                ->each(function ($agreement) use ($days, $settings, &$report) {

                    // Skip if already reminded at this interval
                    $alreadySent = RenewalReminderLog::where('agreement_id', $agreement->id)
                        ->where('days_before_end', $days)
                        ->exists();

                    if ($alreadySent) { $report['skipped']++; return; }

                    $this->sendReminder($agreement, $days, $settings);
                    $report['reminders_sent']++;
                });
        }

        return $report;
    }

    // ── Send reminder ──────────────────────────────────────────────────

    private function sendReminder(RentalAgreement $agreement, int $days, RenewalSettings $settings): void
    {
        $isUrgent    = $days <= 15;
        $isAdminOnly = $days === 15;
        $settings    = RenewalSettings::current();
        $suggestedRent = $settings->suggestNewRent($agreement->monthly_rent);

        // Tenant push + email
        if (! $isAdminOnly && $agreement->customer_id) {
            $title = $days <= 7
                ? '⚠️ Final Reminder — Agreement Ending in ' . $days . ' Days'
                : 'Agreement Ending in ' . $days . ' Days';

            $body = 'Your rental agreement ' . $agreement->agreement_number . ' ends on ' . $agreement->end_date->format('d M Y') . '. '
                . ($days >= 30 ? 'Do you want to renew? Reply from the app.' : 'Please let us know if you want to renew or vacate.');

            $this->push($agreement->customer_id, $title, $body, 'renewal_reminder', [
                'agreement_id'   => $agreement->id,
                'days_remaining' => $days,
                'suggested_rent' => $suggestedRent,
            ]);

            if ($settings->send_email && $agreement->tenant_email) {
                $this->sendEmail($agreement, $days, $suggestedRent);
            }

            if ($settings->send_whatsapp) {
                try {
                    if (class_exists(\App\Plugins\Whatsapp\Services\WhatsappPlatformEventService::class)) {
                        app(\App\Plugins\Whatsapp\Services\WhatsappPlatformEventService::class)
                            ->notifyAgreementRenewal($agreement->fresh(), $days);
                    }
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp agreement_renewal hook failed', [
                        'agreement_id' => $agreement->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Admin alert for 30 days and below
        if ($days <= 30) {
            $this->notifyAdmin($agreement, $days, $suggestedRent);
        }

        // Log it
        RenewalReminderLog::create([
            'agreement_id'   => $agreement->id,
            'customer_id'    => $agreement->customer_id,
            'days_before_end'=> $days,
            'recipient'      => $isAdminOnly ? 'admin' : 'both',
            'channel'        => 'both',
            'status'         => 'sent',
            'sent_at'        => now(),
        ]);
    }

    // ── Record tenant intention ────────────────────────────────────────

    public function recordIntention(RentalAgreement $agreement, string $intention, ?string $notes = null, ?int $customerId = null): RenewalIntention
    {
        $record = RenewalIntention::updateOrCreate(
            ['agreement_id' => $agreement->id],
            [
                'customer_id'  => $customerId ?? $agreement->customer_id,
                'intention'    => $intention,
                'notes'        => $notes,
                'responded_at' => now(),
            ]
        );

        // Notify admin of tenant response
        $this->notifyAdminOfIntention($agreement, $intention);

        return $record;
    }

    // ── Create renewal agreement ───────────────────────────────────────

    public function createRenewal(RentalAgreement $original, ?int $newRent = null, ?int $adminId = null): RentalAgreement
    {
        $settings      = RenewalSettings::current();
        $suggestedRent = $newRent ?? $settings->suggestNewRent($original->monthly_rent);

        return DB::transaction(function () use ($original, $suggestedRent, $adminId) {

            $renewal = RentalAgreement::create([
                'agreement_number'   => $this->numberService->generate(),
                'customer_id'        => $original->customer_id,
                'property_id'        => $original->property_id,
                'status'             => RentalAgreement::STATUS_DRAFT,
                'journey_type'       => $original->journey_type,
                'provider'           => 'manual',
                'owner_name'         => $original->owner_name,
                'owner_phone'        => $original->owner_phone,
                'owner_email'        => $original->owner_email,
                'owner_id_type'      => $original->owner_id_type,
                'owner_id_number'    => $original->owner_id_number,
                'tenant_name'        => $original->tenant_name,
                'tenant_phone'       => $original->tenant_phone,
                'tenant_email'       => $original->tenant_email,
                'tenant_id_type'     => $original->tenant_id_type,
                'tenant_id_number'   => $original->tenant_id_number,
                'property_address'   => $original->property_address,
                'city'               => $original->city,
                'state'              => $original->state,
                'pincode'            => $original->pincode,
                'property_type'      => $original->property_type,
                'monthly_rent'       => $suggestedRent,
                'security_deposit'   => $original->security_deposit,
                'maintenance_amount' => $original->maintenance_amount,
                'rent_due_day'       => $original->rent_due_day,
                'start_date'         => $original->end_date->addDay()->format('Y-m-d'),
                'end_date'           => $original->end_date->addDay()->addMonths(11)->format('Y-m-d'), // 11-month standard
                'lock_in_months'     => $original->lock_in_months,
                'notice_period_days' => $original->notice_period_days,
            ]);

            // Copy clauses
            foreach ($original->selectedClauses as $clause) {
                RentalAgreementSelectedClause::create([
                    'agreement_id' => $renewal->id,
                    'clause_id'    => $clause->clause_id,
                    'title'        => $clause->title,
                    'body'         => $clause->body,
                    'sort_order'   => $clause->sort_order,
                ]);
            }

            // Mark original intention as acted on
            RenewalIntention::where('agreement_id', $original->id)
                ->update(['intention' => RenewalIntention::WANTS_RENEWAL]);

            // Notify tenant
            $this->push(
                $original->customer_id,
                'Agreement Renewed 🎉',
                'Your rental agreement has been renewed. New agreement: ' . $renewal->agreement_number . ' — New rent: ₹' . number_format($suggestedRent),
                'renewal_created',
                ['agreement_id' => $renewal->id, 'agreement_number' => $renewal->agreement_number]
            );

            if ($settings->send_whatsapp) {
                try {
                    if (class_exists(\App\Plugins\Whatsapp\Services\WhatsappPlatformEventService::class)) {
                        app(\App\Plugins\Whatsapp\Services\WhatsappPlatformEventService::class)
                            ->notifyAgreementRenewal($renewal->fresh());
                    }
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp agreement_renewal hook failed', [
                        'agreement_id' => $renewal->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $renewal;
        });
    }

    // ── Notifications ──────────────────────────────────────────────────

    private function push(?int $customerId, string $title, string $body, string $type, array $extra = []): void
    {
        if (! $customerId || ! function_exists('send_push_notification')) return;
        try {
            $tokens = Usertokens::where('customer_id', $customerId)->pluck('fcm_id')->filter()->values()->toArray();
            if (empty($tokens)) return;
            foreach (array_chunk($tokens, 1000) as $batch) {
                send_push_notification($batch, ['title' => $title, 'message' => $body, 'type' => $type, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK', ...$extra]);
            }
        } catch (\Throwable $e) {
            Log::warning('RenewalReminderService: push failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendEmail(RentalAgreement $agreement, int $days, int $suggestedRent): void
    {
        try {
            // TODO: create RenewalReminderMail class (v2 — email templates)
            // Mail::to($agreement->tenant_email)->queue(new RenewalReminderMail($agreement, $days, $suggestedRent));
            Log::info('RenewalReminderService: email queued for ' . $agreement->agreement_number);
        } catch (\Throwable $e) {
            Log::warning('RenewalReminderService: email failed', ['error' => $e->getMessage()]);
        }
    }

    private function notifyAdmin(RentalAgreement $agreement, int $days, int $suggestedRent): void
    {
        // Admin push — if admin tokens available
        try {
            if (class_exists('\App\Models\AdminTokens')) {
                $tokens = \App\Models\AdminTokens::pluck('fcm_id')->filter()->values()->toArray();
                if (! empty($tokens)) {
                    foreach (array_chunk($tokens, 1000) as $batch) {
                        send_push_notification($batch, [
                            'title'          => 'Renewal Alert — ' . $days . ' days',
                            'message'        => $agreement->agreement_number . ' ends ' . $agreement->end_date->format('d M Y') . '. Suggested rent: ₹' . number_format($suggestedRent),
                            'type'           => 'renewal_admin_alert',
                            'click_action'   => 'FLUTTER_NOTIFICATION_CLICK',
                            'agreement_id'   => $agreement->id,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('RenewalReminderService: admin push failed', ['error' => $e->getMessage()]);
        }
    }

    private function notifyAdminOfIntention(RentalAgreement $agreement, string $intention): void
    {
        $labels = [
            'wants_renewal'   => 'wants to RENEW ✅',
            'wants_to_vacate' => 'wants to VACATE ❌',
            'undecided'       => 'is UNDECIDED ⚠️',
        ];

        $this->notifyAdmin(
            $agreement,
            0,
            $agreement->monthly_rent
        );

        Log::info('RenewalReminderService: tenant intention recorded', [
            'agreement_id' => $agreement->id,
            'intention'    => $intention,
        ]);
    }
}
