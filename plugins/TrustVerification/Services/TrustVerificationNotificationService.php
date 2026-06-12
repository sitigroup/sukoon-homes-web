<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Mail\TvOrderSubmittedMail;
use App\Plugins\TrustVerification\Mail\TvReportReadyMail;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Services\HelperService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TrustVerificationNotificationService
{
    public static function orderSubmitted(TvOrder $order): void
    {
        $order->loadMissing(['package', 'subject']);

        $recipients = array_filter(array_unique([
            $order->requester_email,
            $order->subject?->email,
        ]));

        foreach ($recipients as $email) {
            self::sendSafe(new TvOrderSubmittedMail($order), $email);
        }

        $adminEmail = HelperService::getSettingData('company_email')
            ?: HelperService::getSettingData('mail_from_address');

        if ($adminEmail) {
            self::sendSafe(new TvOrderSubmittedMail($order), $adminEmail);
        }
    }

    public static function reportReady(TvOrder $order, ?string $reportUrl = null): void
    {
        $order->loadMissing(['package', 'subject', 'report']);

        $recipients = array_filter(array_unique([
            $order->requester_email,
            $order->subject?->email,
        ]));

        foreach ($recipients as $email) {
            self::sendSafe(new TvReportReadyMail($order, $reportUrl), $email);
        }
    }

    private static function sendSafe($mailable, string $email): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (Throwable $e) {
            Log::warning('Trust verification mail failed: '.$e->getMessage(), [
                'email' => $email,
            ]);
        }
    }
}
