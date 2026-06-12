<?php

namespace App\Plugins\RentPayment\Services;

use App\Models\Usertokens;
use App\Plugins\RentPayment\Models\RentInvoice;
use App\Plugins\RentPayment\Models\RentPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RentNotificationService
{
    public function notifyInvoiceCreated(RentInvoice $invoice): void
    {
        $this->push(
            $invoice->customer_id,
            'Rent Invoice — ' . $invoice->invoice_number,
            'Your rent of ' . $invoice->formattedTotal() . ' is due on ' . $invoice->due_date->format('d M Y') . '.',
            'rent_invoice_created',
            ['invoice_id' => $invoice->id, 'agreement_id' => $invoice->agreement_id]
        );
    }

    public function notifyPaymentConfirmed(RentInvoice $invoice): void
    {
        $this->push(
            $invoice->customer_id,
            'Rent Paid ✅ — ' . $invoice->invoice_number,
            'Your rent payment of ' . $invoice->formattedTotal() . ' has been confirmed.',
            'rent_payment_confirmed',
            ['invoice_id' => $invoice->id]
        );
    }

    public function notifyAwaitingOwnerVerification(RentInvoice $invoice, RentPayment $payment): void
    {
        $modeLabel = $payment->payment_mode === RentPayment::MODE_CASH ? 'cash' : 'bank/UPI transfer';

        $this->push(
            $invoice->customer_id,
            'Payment submitted — ' . $invoice->invoice_number,
            'Your ' . $modeLabel . ' payment claim of ' . $invoice->formattedTotal() . ' is awaiting owner confirmation.',
            'rent_awaiting_verification',
            ['invoice_id' => $invoice->id, 'payment_id' => $payment->id]
        );
    }

    public function notifyPaymentRejected(RentInvoice $invoice, string $reason): void
    {
        $this->push(
            $invoice->customer_id,
            'Payment not confirmed — ' . $invoice->invoice_number,
            'Your landlord did not confirm the payment. Reason: ' . $reason,
            'rent_payment_rejected',
            ['invoice_id' => $invoice->id]
        );
    }

    public function notifyOverdue(RentInvoice $invoice): void
    {
        $this->push(
            $invoice->customer_id,
            'Rent Overdue ⚠️ — ' . $invoice->invoice_number,
            'Your rent of ' . $invoice->formattedTotal() . ' is overdue. Please pay immediately.',
            'rent_overdue',
            ['invoice_id' => $invoice->id]
        );
    }

    public function notifyReminder(RentInvoice $invoice, int $daysOverdue): void
    {
        $this->push(
            $invoice->customer_id,
            'Rent Reminder — ' . $invoice->invoice_number,
            'Reminder: Your rent of ' . $invoice->formattedTotal() . ' is ' . $daysOverdue . ' day(s) overdue.',
            'rent_reminder',
            ['invoice_id' => $invoice->id, 'days_overdue' => $daysOverdue]
        );
    }

    private function push(?int $customerId, string $title, string $body, string $type, array $extra = []): void
    {
        if (! $customerId || ! function_exists('send_push_notification')) return;

        try {
            $tokens = Usertokens::where('customer_id', $customerId)
                ->pluck('fcm_id')->filter()->values()->toArray();

            if (empty($tokens)) return;

            foreach (array_chunk($tokens, 1000) as $batch) {
                send_push_notification($batch, [
                    'title'        => $title,
                    'message'      => $body,
                    'type'         => $type,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ...$extra,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('RentNotificationService: push failed', ['error' => $e->getMessage()]);
        }
    }
}
