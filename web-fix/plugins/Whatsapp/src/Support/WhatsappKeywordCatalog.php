<?php

namespace App\Plugins\Whatsapp\Support;

class WhatsappKeywordCatalog
{
    /**
     * Keyword rules in priority order (first match wins).
     *
     * @return array<int, array{key:string,pattern:string,tags:array<int,string>,reply_setting:string,default_reply:string}>
     */
    public static function rules(): array
    {
        return [
            [
                'key' => 'repair',
                'pattern' => '/\b(repair|maintenance|leak|plumb|fix|broken)\b/i',
                'tags' => ['maintenance'],
                'reply_setting' => 'keyword_reply_repair',
                'default_reply' => 'Thanks for your repair message. Please share a photo if you can. A Sukoon Homes agent will follow up shortly.',
            ],
            [
                'key' => 'rent',
                'pattern' => '/\b(rent|payment|pay|kiraya|kiraye)\b/i',
                'tags' => ['payment_pending'],
                'reply_setting' => 'keyword_reply_rent',
                'default_reply' => 'Thanks for reaching out about rent. Our team will assist you shortly with payment details.',
            ],
            [
                'key' => 'agreement',
                'pattern' => '/\b(agreement|renew|renewal|lease)\b/i',
                'tags' => ['renewal_due'],
                'reply_setting' => 'keyword_reply_agreement',
                'default_reply' => 'Thanks for your message about your rental agreement. Our team will help with renewal or documents shortly.',
            ],
        ];
    }

    /**
     * @return array{key:string,pattern:string,tags:array<int,string>,reply_setting:string,default_reply:string}|null
     */
    public static function match(string $text): ?array
    {
        $haystack = trim($text);
        if ($haystack === '') {
            return null;
        }

        foreach (self::rules() as $rule) {
            if (preg_match($rule['pattern'], $haystack)) {
                return $rule;
            }
        }

        return null;
    }
}
