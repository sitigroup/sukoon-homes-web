<?php

namespace App\Plugins\TrustVerification\Mail;

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TvReportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TvOrder $order, public ?string $reportUrl = null)
    {
        $this->order->loadMissing(['package', 'subject', 'report']);
    }

    public function envelope(): Envelope
    {
        $tpl = TrustVerificationContentService::emailTemplate('email.report_ready', [
            'order_number' => $this->order->order_number,
        ], [
            'subject' => 'Verification report ready — '.$this->order->order_number,
        ]);

        return new Envelope(
            subject: $tpl['subject'],
        );
    }

    public function content(): Content
    {
        $tpl = TrustVerificationContentService::emailTemplate('email.report_ready', [
            'order_number' => $this->order->order_number,
        ], []);

        return new Content(
            view: 'trust-verification::emails.report-ready',
            with: ['cmsBodyHtml' => $tpl['body_html']],
        );
    }
}
