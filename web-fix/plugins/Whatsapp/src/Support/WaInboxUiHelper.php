<?php

namespace App\Plugins\Whatsapp\Support;

use App\Plugins\Whatsapp\Models\WaMessage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class WaInboxUiHelper
{
    public static function initials(?string $phone, ?string $customerType = null): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) >= 2) {
            return strtoupper(substr($digits, -2));
        }

        if ($customerType) {
            return strtoupper(substr((string) $customerType, 0, 2));
        }

        return '?';
    }

    public static function displayLabel(?string $phone, ?string $customerType = null): string
    {
        $phone = trim((string) $phone);
        if ($phone !== '') {
            return $phone;
        }

        return $customerType ? ucfirst((string) $customerType) : '-';
    }

    public static function previewText(?WaMessage $message, int $max = 56): string
    {
        if (! $message) {
            return '';
        }

        $text = $message->displayBody();
        if ($message->type === 'template') {
            $text = __('whatsapp::whatsapp.msg_type_template').': '.$text;
        }

        return Str::limit(trim((string) $text), $max);
    }

    public static function listTime(?Carbon $at): string
    {
        if (! $at) {
            return '';
        }

        if ($at->isToday()) {
            return $at->format('H:i');
        }

        if ($at->isYesterday()) {
            return __('whatsapp::whatsapp.yesterday');
        }

        if ($at->greaterThan(now()->subDays(6))) {
            return $at->format('D');
        }

        return $at->format('d M');
    }

    public static function bubbleTime(?Carbon $at): string
    {
        return $at ? $at->format('H:i') : '';
    }

    public static function dateSeparatorLabel(?Carbon $at): string
    {
        if (! $at) {
            return '';
        }

        if ($at->isToday()) {
            return __('whatsapp::whatsapp.today');
        }

        if ($at->isYesterday()) {
            return __('whatsapp::whatsapp.yesterday');
        }

        return $at->format('l, j F Y');
    }

    public static function statusColorClass(string $status): string
    {
        return match ($status) {
            'open' => 'wa-status--open',
            'pending' => 'wa-status--pending',
            'resolved' => 'wa-status--resolved',
            default => 'wa-status--open',
        };
    }

    public static function hasUnreadHint(?WaMessage $lastMessage, string $conversationStatus): bool
    {
        if ($conversationStatus !== 'open' || ! $lastMessage) {
            return false;
        }

        return $lastMessage->direction === 'in';
    }
}
