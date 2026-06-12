<?php

namespace App\Plugins\TrustVerification\Mail;

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TvOrderSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TvOrder $order)
    {
        $this->order->loadMissing(['package', 'subject']);
    }

    public function envelope(): Envelope
    {
        $tpl = TrustVerificationContentService::emailTemplate('email.order_submitted', [
            'order_number' => $this->order->order_number,
            'subject_name' => $this->order->subject?->full_name ?? '',
        ], [
            'subject' => 'Verification request received — '.$this->order->order_number,
        ]);

        return new Envelope(
            subject: $tpl['subject'],
        );
    }

    public function content(): Content
    {
        $tpl = TrustVerificationContentService::emailTemplate('email.order_submitted', [
            'order_number' => $this->order->order_number,
            'subject_name' => $this->order->subject?->full_name ?? '',
        ], []);

        return new Content(
            view: 'trust-verification::emails.order-submitted',
            with: ['cmsBodyHtml' => $tpl['body_html']],
        );
    }
}
