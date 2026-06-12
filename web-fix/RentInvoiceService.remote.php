<?php

namespace App\Plugins\RentPayment\Services;

use App\Plugins\RentPayment\Models\OwnerPayout;
use App\Plugins\RentPayment\Models\RentInvoice;
use App\Plugins\RentPayment\Models\RentPayment;
use App\Plugins\RentPayment\Models\RentSettings;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use App\Plugins\RentalAgreement\Support\RentalAgreementRentPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class RentInvoiceService
{
    public function __construct(
        private readonly RentNotificationService $notificationService,
    ) {}


    /**
     * Advance rent: due on rent_due_day within the billing month (pay before/at period start).
     *
     * @return array{period_start: string, period_end: string, due_date: string}
     */
    public function advanceBillingPeriod(RentalAgreement $agreement, ?Carbon $billingMonth = null): array
    {
        $months     = RentalAgreementRentPlan::billingMonths($agreement);
        $leaseStart = RentalAgreementRentPlan::leaseStartDay($agreement)
            ?? Carbon::now()->startOfDay();

        if ($billingMonth) {
            $periodStart = $billingMonth->copy()->startOfDay();
        } else {
            $last = RentInvoice::where('agreement_id', $agreement->id)
                ->orderByDesc('period_start')
                ->first();

            if ($last) {
                $periodStart = Carbon::parse($last->period_end)->addDay()->startOfDay();
            } else {
                $periodStart = $leaseStart->copy();
            }
        }

        if ($periodStart->lt($leaseStart)) {
            $periodStart = $leaseStart->copy();
        }

        $periodEnd = $periodStart->copy()->addMonths($months - 1)->endOfMonth();
        $dueDate   = RentalAgreementRentPlan::dueDateForPeriodStart($agreement, $periodStart);
        $dueDate   = RentalAgreementRentPlan::clampDueOnOrAfterLease($agreement, $dueDate);

        return [
            'period_start' => $periodStart->toDateString(),
            'period_end'   => $periodEnd->toDateString(),
            'due_date'     => $dueDate->toDateString(),
            'billing_months' => $months,
        ];
    }

    public function firstAdvanceBillingPeriod(RentalAgreement $agreement): array
    {
        $start = RentalAgreementRentPlan::leaseStartDay($agreement)
            ?? Carbon::now()->startOfDay();

        return $this->advanceBillingPeriod($agreement, $start);
    }

    public function createTenantPeriodInvoice(
        RentalAgreement $agreement,
        string $periodStart,
        string $periodEnd,
        int $customerId
    ): RentInvoice {
        if (! RentalAgreementRentPlan::tenantCanPickPeriod($agreement)) {
            throw new \InvalidArgumentException(__('This agreement does not allow choosing a rent period.'));
        }

        $from = Carbon::parse($periodStart)->startOfMonth();
        $to   = Carbon::parse($periodEnd)->endOfMonth();
        $months = RentalAgreementRentPlan::countMonthsInclusive($from, $to);

        if ($months < RentalAgreementRentPlan::minMonths($agreement)
            || $months > RentalAgreementRentPlan::maxMonths($agreement)) {
            throw new \InvalidArgumentException(__('Selected period length is not allowed for this agreement.'));
        }

        if ($agreement->start_date && $from->lt(Carbon::parse($agreement->start_date)->startOfMonth())) {
            throw new \InvalidArgumentException(__('Period is before lease start.'));
        }

        if ($agreement->end_date && $to->gt(Carbon::parse($agreement->end_date)->endOfMonth())) {
            throw new \InvalidArgumentException(__('Period is after lease end.'));
        }

        if (RentalAgreementRentPlan::periodOverlapsExisting($agreement, $from, $to)) {
            throw new \InvalidArgumentException(__('An invoice already exists for part of this period.'));
        }

        $dueDate = RentalAgreementRentPlan::dueDateForPeriodStart($agreement, $from);

        return $this->create($agreement, [
            'period_start' => $from->toDateString(),
            'period_end'   => $to->toDateString(),
            'due_date'     => $dueDate->toDateString(),
            'billing_months' => $months,
            'notes'        => __('Tenant-selected rent period'),
        ]);
    }

    public function createNextPeriodInvoice(RentalAgreement $agreement, int $customerId): RentInvoice
    {
        if (! RentalAgreementRentPlan::allowPayNextPeriod($agreement)) {
            throw new \InvalidArgumentException(__('Pay next period is not enabled on this agreement.'));
        }

        $period = $this->advanceBillingPeriod($agreement);

        if (RentalAgreementRentPlan::periodOverlapsExisting(
            $agreement,
            Carbon::parse($period['period_start']),
            Carbon::parse($period['period_end'])
        )) {
            throw new \InvalidArgumentException(__('Next period invoice already exists.'));
        }

        return $this->create($agreement, $period);
    }

    private function nextInvoiceNumber(): string
    {
        $last = RentInvoice::whereYear('created_at', now()->year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_number');

        $next = $last
            ? (int) substr($last, -6) + 1
            : 1;

        return 'INV-' . now()->year . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    public function create(RentalAgreement $agreement, array $overrides = []): RentInvoice
    {
        $settings = RentSettings::current();
        $advance     = $this->advanceBillingPeriod($agreement);
        $periodStart = $overrides['period_start'] ?? $advance['period_start'];
        $periodEnd   = $overrides['period_end']   ?? $advance['period_end'];
        $dueDate     = $overrides['due_date']      ?? $advance['due_date'];
        $months      = (int) ($overrides['billing_months'] ?? RentalAgreementRentPlan::countMonthsInclusive(
            Carbon::parse($periodStart),
            Carbon::parse($periodEnd)
        ));
        $amounts     = RentalAgreementRentPlan::amountsForMonths($agreement, $months);
        $rentAmount  = $amounts['rent'];
        $maint       = $amounts['maintenance'];
        $commission  = $amounts['commission'];
        $tenantPays  = $amounts['total'];

        return DB::transaction(function () use ($agreement, $settings, $rentAmount, $maint, $commission, $tenantPays, $periodStart, $periodEnd, $dueDate, $overrides) {

            $invoice = RentInvoice::create([
                'invoice_number'     => $this->nextInvoiceNumber(),
                'agreement_id'       => $agreement->id,
                'customer_id'        => $this->resolveInvoiceCustomerId($agreement),
                'period_start'       => $periodStart,
                'period_end'         => $periodEnd,
                'due_date'           => $dueDate,
                'rent_amount'        => $rentAmount,
                'maintenance_amount' => $maint,
                'commission_amount'  => $commission,
                'commission_type'    => $settings->commission_type,
                'commission_payer'   => $settings->commission_payer,
                'total_amount'       => $tenantPays,
                'collection_mode'    => $overrides['collection_mode'] ?? $settings->collection_mode,
                'status'             => RentInvoice::STATUS_PENDING,
                'notes'              => $overrides['notes'] ?? null,
            ]);

            $this->notificationService->notifyInvoiceCreated($invoice);

            return $invoice;
        });
    }

    private function cleanCashfreeSessionId(?string $id): ?string
    {
        return $id ? preg_replace('/paymentpayment$/i', '', $id) : null;
    }

    private function resolveCashfreeCustomerDetails(RentInvoice $invoice): array
    {
        $invoice->loadMissing('agreement');
        $agreement = $invoice->agreement;
        $customer  = $invoice->customer_id
            ? \App\Models\Customer::find($invoice->customer_id)
            : null;

        $phone = preg_replace('/\D/', '', (string) ($agreement?->tenant_phone ?? ''));
        if (strlen($phone) < 10 || preg_match('/^0+$/', $phone)) {
            $phone = preg_replace('/\D/', '', (string) ($customer?->mobile ?? ''));
        }
        $phone = strlen($phone) >= 10 ? substr($phone, -10) : $phone;

        $email = filter_var((string) ($agreement?->tenant_email ?? ''), FILTER_VALIDATE_EMAIL)
            ? $agreement->tenant_email
            : (filter_var((string) ($customer?->email ?? ''), FILTER_VALIDATE_EMAIL)
                ? $customer->email
                : 'customer' . $invoice->customer_id . '@sukoon.group');

        $name = ($agreement?->tenant_name && $agreement->tenant_name !== 'Pending')
            ? $agreement->tenant_name
            : ($customer?->name ?? 'Tenant');

        if (strlen($phone) < 10) {
            throw new \RuntimeException(__('A valid 10-digit phone number is required to pay online. Please update your profile.'));
        }

        return [
            'customer_id'    => (string) $invoice->customer_id,
            'customer_name'  => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
        ];
    }

    public function createCashfreeOrder(RentInvoice $invoice): RentInvoice
    {
        try {
            $settings = \App\Services\HelperService::getPaymentDetails('cashfree');
            if (empty($settings['cashfree_app_id'] ?? null) || empty($settings['cashfree_secret_key'] ?? null)) {
                throw new \RuntimeException(__('Cashfree payments are not configured.'));
            }

            $customer = $this->resolveCashfreeCustomerDetails($invoice);
            $webUrl   = rtrim(trim((string) env('WEB_URL', 'https://homes.sukoon.group')), '/');

            $metadata = [
                'email'         => $customer['customer_email'],
                'platform_type' => 'web',
                'description'   => 'Rent payment — ' . $invoice->invoice_number,
                'user_name'     => $customer['customer_name'],
                'phone'         => $customer['customer_phone'],
                'return_url'    => $webUrl . '/payment/rent-return?invoice_id=' . $invoice->id,
                'link_notes'    => [
                    'rent_invoice_id'     => (string) $invoice->id,
                    'rent_invoice_number' => $invoice->invoice_number,
                ],
            ];

            $intent = \App\Services\Payment\PaymentService::create($settings)
                ->createAndFormatPaymentIntent((float) $invoice->total_amount, $metadata);

            $linkId     = $intent['id'] ?? '';
            $paymentUrl = $intent['payment_url'] ?? '';

            if (! $paymentUrl) {
                throw new \RuntimeException(__('Unable to start payment. Please try again.'));
            }

            $invoice->update([
                'cashfree_order_id'     => $linkId,
                'cashfree_payment_link' => $paymentUrl,
            ]);
        } catch (\Throwable $e) {
            Log::error('RentInvoiceService: Cashfree payment link creation failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }

        return $invoice->fresh();
    }

    public function confirmCashfreePayment(array $payload): void
    {
        $eventType = strtoupper((string) ($payload['type'] ?? $payload['event'] ?? ''));
        $linkId    = $payload['data']['link_id'] ?? null;

        if ($linkId) {
            $invoice = RentInvoice::where('cashfree_order_id', $linkId)->first();
            if (! $invoice || $invoice->status === RentInvoice::STATUS_PAID) {
                return;
            }

            $linkStatus = strtoupper((string) ($payload['data']['link_status'] ?? ''));
            $successEvents = ['PAYMENT_LINK_EVENT', 'PAYMENT_SUCCESS_WEBHOOK', 'PAYMENT_SUCCESS', 'ORDER_PAID'];
            $isPaid = $linkStatus === 'PAID' || in_array($eventType, $successEvents, true);

            if (! $isPaid) {
                return;
            }

            DB::transaction(function () use ($invoice, $payload, $linkId) {
                $payment = RentPayment::create([
                    'invoice_id'              => $invoice->id,
                    'agreement_id'            => $invoice->agreement_id,
                    'customer_id'             => $invoice->customer_id,
                    'payment_mode'            => RentPayment::MODE_CASHFREE,
                    'amount_paid'             => $payload['data']['link_amount_paid'] ?? $invoice->total_amount,
                    'cashfree_order_id'       => $linkId,
                    'cashfree_payment_id'     => $payload['data']['cf_payment_id'] ?? null,
                    'cashfree_payment_method' => 'payment_link',
                    'cashfree_response'       => $payload,
                    'status'                  => RentPayment::STATUS_CONFIRMED,
                    'confirmed_at'            => now(),
                ]);

                $this->finalizeTenantPayment($invoice->fresh(), $payment);
            });

            return;
        }

        $orderId = $payload['data']['order']['order_id'] ?? null;
        if (! $orderId) {
            return;
        }

        $invoice = RentInvoice::where('cashfree_order_id', $orderId)->first();
        if (! $invoice || $invoice->status === RentInvoice::STATUS_PAID) {
            return;
        }

        DB::transaction(function () use ($invoice, $payload, $orderId) {
            $paymentId     = $payload['data']['payment']['cf_payment_id'] ?? null;
            $paymentMethod = $payload['data']['payment']['payment_method'] ?? 'unknown';
            $amountPaid    = $payload['data']['payment']['payment_amount'] ?? $invoice->total_amount;
            $eventType     = $payload['type'] ?? '';

            if ($eventType === 'PAYMENT_SUCCESS_WEBHOOK') {
                $payment = RentPayment::create([
                    'invoice_id'              => $invoice->id,
                    'agreement_id'            => $invoice->agreement_id,
                    'customer_id'             => $invoice->customer_id,
                    'payment_mode'            => RentPayment::MODE_CASHFREE,
                    'amount_paid'             => $amountPaid,
                    'cashfree_order_id'       => $orderId,
                    'cashfree_payment_id'     => $paymentId,
                    'cashfree_payment_method' => $paymentMethod,
                    'cashfree_response'       => $payload,
                    'status'                  => RentPayment::STATUS_CONFIRMED,
                    'confirmed_at'            => now(),
                ]);

                $this->finalizeTenantPayment($invoice->fresh(), $payment);
            }
        });
    }

    public function confirmDirectPayment(RentInvoice $invoice, array $data, $file = null, ?\Carbon\Carbon $paidAt = null): RentInvoice
    {
        $receiptPath = null;
        $receiptType = null;

        if ($file) {
            $ext         = $file->getClientOriginalExtension();
            $receiptPath = 'rent-receipts/' . $invoice->id . '-' . time() . '.' . $ext;
            $receiptType = in_array(strtolower($ext), ['jpg','jpeg','png']) ? 'photo' : 'pdf';
            Storage::disk('local')->put($receiptPath, file_get_contents($file->getRealPath()));
        }

        return DB::transaction(function () use ($invoice, $data, $receiptPath, $receiptType, $paidAt) {
            $payment = RentPayment::create([
                'invoice_id'        => $invoice->id,
                'agreement_id'      => $invoice->agreement_id,
                'customer_id'       => $invoice->customer_id,
                'payment_mode'      => RentPayment::MODE_DIRECT,
                'amount_paid'       => $invoice->total_amount,
                'receipt_path'      => $receiptPath,
                'receipt_type'      => $receiptType,
                'payment_reference' => $data['reference'] ?? null,
                'status'            => RentPayment::STATUS_CONFIRMED,
                'notes'             => $data['notes'] ?? null,
                'confirmed_at'      => $paidAt ?? now(),
            ]);

            return $this->finalizeTenantPayment($invoice, $payment, $paidAt);
        });
    }


    /**
     * After a period is paid, open the next billing period invoice (advance rent).
     */
    public function ensureNextPendingInvoice(RentalAgreement $agreement): ?RentInvoice
    {
        if (! RentalAgreementRentPlan::autoCreatesInvoices($agreement)) {
            return null;
        }

        if (in_array($agreement->status, ['cancelled', 'draft'], true)) {
            return null;
        }

        $hasOpen = RentInvoice::where('agreement_id', $agreement->id)
            ->whereNotIn('status', [RentInvoice::STATUS_PAID, RentInvoice::STATUS_CANCELLED])
            ->exists();

        if ($hasOpen) {
            return null;
        }

        $period = $this->advanceBillingPeriod($agreement);
        $periodStart = Carbon::parse($period['period_start'])->startOfMonth();

        if ($agreement->end_date && $periodStart->gt(Carbon::parse($agreement->end_date)->endOfMonth())) {
            return null;
        }

        $duplicate = RentInvoice::where('agreement_id', $agreement->id)
            ->whereDate('period_start', $period['period_start'])
            ->exists();

        if ($duplicate) {
            return null;
        }

        return $this->create($agreement, $period);
    }


    public function finalizeTenantPayment(RentInvoice $invoice, ?RentPayment $payment = null, ?\Carbon\Carbon $paidAt = null): RentInvoice
    {
        if ($invoice->status === RentInvoice::STATUS_PAID) {
            return $invoice;
        }

        $invoice->update([
            'status'  => RentInvoice::STATUS_PAID,
            'paid_at' => $paidAt ?? now(),
        ]);

        $invoice = $invoice->fresh();
        $this->createOwnerPayout($invoice);
        $this->recordAgentCommission($invoice);
        $this->notificationService->notifyPaymentConfirmed($invoice);

        try {
            if (class_exists(\App\Plugins\Whatsapp\Services\WhatsappPlatformEventService::class)) {
                app(\App\Plugins\Whatsapp\Services\WhatsappPlatformEventService::class)
                    ->notifyRentReceived($invoice->load('agreement'));
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp rent_received hook failed', [
                'invoice_id' => $invoice->id,
                'error'        => $e->getMessage(),
            ]);
        }

        $agreement = $invoice->agreement;
        if ($agreement) {
            try {
                $this->ensureNextPendingInvoice($agreement);
            } catch (\Throwable $e) {
                Log::warning('RentInvoiceService: ensureNextPendingInvoice failed', [
                    'agreement_id' => $agreement->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        return $invoice;
    }

    private function createOwnerPayout(RentInvoice $invoice): void
    {
        if (OwnerPayout::where('invoice_id', $invoice->id)->exists()) {
            return;
        }

        $settings     = RentSettings::current();
        $commission   = $invoice->commission_payer === 'owner' ? $invoice->commission_amount : 0;
        $payoutAmount = $invoice->rent_amount + ($invoice->maintenance_amount ?? 0) - $commission;

        OwnerPayout::create([
            'invoice_id'        => $invoice->id,
            'agreement_id'      => $invoice->agreement_id,
            'gross_amount'      => $invoice->total_amount,
            'commission_amount' => $commission,
            'payout_amount'     => max(0, $payoutAmount),
            'owner_name'        => $invoice->agreement?->owner_name,
            'status'            => OwnerPayout::STATUS_PENDING,
        ]);
    }

    private function recordAgentCommission(RentInvoice $invoice): void
    {
        try {
            if (! class_exists(\App\Plugins\AreaManagement\Services\AgentCommissionService::class)) {
                return;
            }

            app(\App\Plugins\AreaManagement\Services\AgentCommissionService::class)
                ->calculateForRentPayment($invoice);
        } catch (\Throwable $e) {
            Log::warning('RentInvoiceService: agent commission hook failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function markOverdue(RentInvoice $invoice): void
    {
        if ($invoice->status === RentInvoice::STATUS_PENDING && $invoice->isOverdue()) {
            $invoice->update(['status' => RentInvoice::STATUS_OVERDUE]);
            $this->notificationService->notifyOverdue($invoice);
        }
    }

    public function waive(RentInvoice $invoice, string $reason): void
    {
        $invoice->update(['status' => RentInvoice::STATUS_WAIVED, 'notes' => $reason]);
    }

    private function resolveInvoiceCustomerId(\App\Plugins\RentalAgreement\Models\RentalAgreement $agreement): ?int
    {
        if (! empty($agreement->customer_id)) {
            return (int) $agreement->customer_id;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('agreement_requests')) {
                $ar = \Illuminate\Support\Facades\DB::table('agreement_requests')
                    ->where('created_agreement_id', $agreement->id)
                    ->whereNotNull('tenant_customer_id')
                    ->first();
                if ($ar && (int) $ar->tenant_customer_id > 0) {
                    return (int) $ar->tenant_customer_id;
                }
            }

            $phone = preg_replace('/\D+/', '', (string) $agreement->tenant_phone);
            if (strlen($phone) >= 10) {
                $last10 = substr($phone, -10);
                $customer = \App\Models\Customer::query()
                    ->where('mobile', 'like', '%' . $last10)
                    ->first();
                if ($customer) {
                    return (int) $customer->id;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
