<?php

namespace App\Plugins\RentPayment\Http\Controllers\Api;

use App\Plugins\RentPayment\Support\RentDateTime;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use App\Plugins\RentalAgreement\Support\RentalAgreementRentPlan;

use App\Plugins\RentPayment\Models\RentInvoice;
use App\Plugins\RentPayment\Models\RentPayment;
use App\Plugins\RentPayment\Services\RentInvoiceService;
use App\Plugins\RentPayment\Services\RentInvoiceVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Plugins\RentPayment\Support\RentReceiptResponse;
use Illuminate\Support\Facades\Storage;

class RentPaymentApiController extends Controller
{
    public function __construct(
        private readonly RentInvoiceService $invoiceService,
        private readonly RentInvoiceVerificationService $verificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $customerId = (int) auth()->id();
        $invoices = RentInvoice::query()
            ->accessibleByCustomer($customerId)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->with(['latestPayment', 'agreement'])
            ->latest()
            ->get()
            ->filter(fn ($i) => $this->tenantListVisible($i))
            ->map(fn ($i) => $this->format($i))
            ->values();

        return response()->json([
            'data'          => $invoices,
            'payment_plan'  => $this->resolvePaymentPlan((int) auth()->id()),
        ]);
    }

    public function show(RentInvoice $invoice): JsonResponse
    {
        if (! $invoice->isAccessibleByCustomer((int) auth()->id())) abort(403);

        return response()->json(['data' => $this->format($invoice, true)]);
    }

    public function pay(RentInvoice $invoice): JsonResponse
    {
        if (! $invoice->isAccessibleByCustomer((int) auth()->id())) abort(403);
        if ($invoice->status === RentInvoice::STATUS_PAID) {
            return response()->json(['message' => __('Already paid.')], 422);
        }

        if (! $invoice->isPaymentWindowOpen()) {
            return response()->json([
                'message' => __('Rent payment opens on :date.', [
                    'date' => $invoice->paymentWindowOpensAt()->format('d M Y'),
                ]),
            ], 422);
        }

        $invoice->update(['cashfree_order_id' => null, 'cashfree_payment_link' => null]);
        $invoice = $this->invoiceService->createCashfreeOrder($invoice->fresh());

        return response()->json([
            'payment_link' => $invoice->cashfree_payment_link,
            'order_id'     => $invoice->cashfree_order_id,
        ]);
    }

    public function submitProof(Request $request, RentInvoice $invoice): JsonResponse
    {
        $customerId = (int) auth()->id();
        if (! $invoice->isAccessibleByCustomer($customerId)) abort(403);

        $request->validate([
            'proof'     => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'reference' => 'nullable|string|max:120',
            'notes'     => 'nullable|string|max:500',
        ]);

        try {
            $payment = $this->verificationService->submitProof(
                $invoice,
                $customerId,
                $request->only(['reference', 'notes']),
                $request->file('proof')
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('Payment proof submitted. Awaiting owner confirmation.'),
            'data'    => $this->format($invoice->fresh()->load('latestPayment')),
            'claim'   => $this->verificationService->formatClaim($payment),
        ]);
    }

    public function reportCash(Request $request, RentInvoice $invoice): JsonResponse
    {
        $customerId = (int) auth()->id();
        if (! $invoice->isAccessibleByCustomer($customerId)) abort(403);

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $payment = $this->verificationService->reportCash(
                $invoice,
                $customerId,
                $request->only(['notes'])
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('Cash payment reported. Awaiting owner confirmation.'),
            'data'    => $this->format($invoice->fresh()->load('latestPayment')),
            'claim'   => $this->verificationService->formatClaim($payment),
        ]);
    }

    public function receipt(RentPayment $payment)
    {
        $customerId = (int) auth()->id();
        $payment->loadMissing('invoice');

        if (! $payment->invoice?->isAccessibleByCustomer($customerId)) {
            abort(403);
        }

        return RentReceiptResponse::inline($payment);
    }

    public function webhook(Request $request): JsonResponse
    {
        $this->invoiceService->confirmCashfreePayment($request->all());
        return response()->json(['status' => 'ok']);
    }

    private function tenantListVisible(RentInvoice $i): bool
    {
        if ($i->isPaymentWindowOpen()) {
            return true;
        }

        return in_array($i->status, [
            RentInvoice::STATUS_PAID,
            RentInvoice::STATUS_AWAITING_VERIFICATION,
            RentInvoice::STATUS_OVERDUE,
        ], true);
    }


    public function createCustomPeriod(Request $request): JsonResponse
    {
        $customerId = (int) auth()->id();
        $data = $request->validate([
            'agreement_id'  => 'required|integer|min:1',
            'period_start'  => 'required|date',
            'period_end'    => 'required|date|after_or_equal:period_start',
        ]);

        $agreement = RentalAgreement::query()->findOrFail((int) $data['agreement_id']);
        if (! $agreement->isAccessibleByCustomer($customerId)) {
            abort(403);
        }

        try {
            $invoice = $this->invoiceService->createTenantPeriodInvoice(
                $agreement,
                $data['period_start'],
                $data['period_end'],
                $customerId
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('Invoice created for selected period.'),
            'data'    => $this->format($invoice->load('latestPayment')),
        ], 201);
    }

    public function createNextPeriod(Request $request): JsonResponse
    {
        $customerId = (int) auth()->id();
        $data = $request->validate([
            'agreement_id' => 'required|integer|min:1',
        ]);

        $agreement = RentalAgreement::query()->findOrFail((int) $data['agreement_id']);
        if (! $agreement->isAccessibleByCustomer($customerId)) {
            abort(403);
        }

        try {
            $invoice = $this->invoiceService->createNextPeriodInvoice($agreement, $customerId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('Next period invoice created.'),
            'data'    => $this->format($invoice->load('latestPayment')),
        ], 201);
    }

    private function resolvePaymentPlan(int $customerId): ?array
    {
        $agreement = RentalAgreement::query()
            ->accessibleByCustomer($customerId)
            ->where('status', RentalAgreement::STATUS_COMPLETED)
            ->orderByDesc('id')
            ->first();

        return $agreement ? RentalAgreementRentPlan::toArray($agreement) : null;
    }

    private function format(RentInvoice $i, bool $full = false): array
    {
        $claim = null;
        if ($i->relationLoaded('latestPayment') && $i->latestPayment) {
            $claim = $this->verificationService->formatClaim($i->latestPayment);
        }

        $base = [
            'id'             => $i->id,
            'invoice_number' => $i->invoice_number,
            'period'         => $i->periodLabel(),
            'due_date'       => RentDateTime::format($i->due_date, false),
            'total_amount'   => $i->total_amount,
            'formatted_total'=> $i->formattedTotal(),
            'status'         => $i->status,
            'is_overdue'     => $i->isOverdue(),
            'payment_link'   => $i->cashfree_payment_link,
            'paid_at'        => RentDateTime::format($i->paid_at),
            'collection_mode'=> $i->collection_mode,
            'claim'               => $claim,
            'payment_window_open' => $i->isPaymentWindowOpen(),
            'payable_from'        => RentDateTime::format($i->paymentWindowOpensAt(), false),
        ];

        if ($full) {
            $base['rent_amount']        = $i->rent_amount;
            $base['maintenance_amount'] = $i->maintenance_amount;
            $base['commission_amount']  = $i->commission_amount;
            $base['commission_payer']   = $i->commission_payer;
        }

        return $base;
    }
}
