<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function surveyLink(string $uuid): string
    {
        $baseUrl = rtrim(self::get('survey_public_url', 'https://trumark.emca.tech'), '/');

        return $baseUrl . '/feedback/' . $uuid;
    }

    /**
     * Whether SMS may be sent for a given CRM action.
     * Respects the global SMS master switch plus per-scenario toggles.
     */
    public static function isSmsEnabled(?string $scenario = null): bool
    {
        if (self::get('sms_channel_enabled', '1') !== '1') {
            return false;
        }

        if ($scenario === null) {
            return true;
        }

        $defaults = [
            'welcome' => '0',
            'survey' => '1',
            'followup' => '1',
            'bulk' => '1',
            'manual' => '1',
            'lead_assignment' => '1',
            'staff' => '1',
            'campaign' => '1',
        ];

        $keys = [
            'welcome' => 'welcome_channels_sms',
            'survey' => 'survey_channels_sms',
            'followup' => 'followup_channels_sms',
            'bulk' => 'sms_allow_bulk',
            'manual' => 'sms_allow_manual',
            'lead_assignment' => 'sms_allow_lead_assignment',
            'staff' => 'sms_allow_staff',
            'campaign' => 'sms_allow_campaign',
        ];

        $key = $keys[$scenario] ?? null;
        if (!$key) {
            return true;
        }

        return self::get($key, $defaults[$scenario] ?? '1') === '1';
    }

    /**
     * Build WhatsApp bot payment instructions from fully customizable Settings.
     *
     * Prefer a full custom message. Otherwise build from payment option lines:
     *   Method Name|Details
     * Legacy payment_* keys are still read as a fallback.
     */
    public static function whatsappPaymentMessage(): string
    {
        $customMessage = trim(self::get('wa_payment_message', ''));
        if ($customMessage !== '') {
            return $customMessage;
        }

        $intro = trim(self::get('wa_payment_intro', ''));
        if ($intro === '') {
            $intro = "💳 *NJIA ZA MALIPO / PAYMENT METHODS*";
        }

        $footer = trim(self::get('wa_payment_footer', ''));
        if ($footer === '') {
            $footer = "✅ Baada ya kulipa, *tuma picha ya muamala (screenshot)* hapa ili tuthibitishe na kuanza mzigo wako mara moja!";
        }

        $optionsRaw = trim(self::get('wa_payment_options', ''));
        $lines = [];

        if ($optionsRaw !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $optionsRaw) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                if (str_contains($line, '|')) {
                    [$label, $details] = array_map('trim', explode('|', $line, 2));
                    if ($label === '') {
                        continue;
                    }
                    $lines[] = $details !== ''
                        ? "• *{$label}*: {$details}"
                        : "• *{$label}*";
                } else {
                    $lines[] = "• {$line}";
                }
            }
        }

        // Legacy fallback if new options are empty but old fields still have values
        if (empty($lines)) {
            $legacy = [
                'M-Pesa' => trim(self::get('payment_mpesa', '')),
                'Tigo Pesa' => trim(self::get('payment_tigo', '')),
                'Airtel Money' => trim(self::get('payment_airtel', '')),
            ];
            foreach ($legacy as $label => $value) {
                if ($value !== '') {
                    $lines[] = "• *{$label}*: {$value}";
                }
            }

            $bankName = trim(self::get('payment_bank_name', ''));
            $bankAccount = trim(self::get('payment_bank_account', ''));
            $bankHolder = trim(self::get('payment_bank_holder', ''));
            if ($bankName !== '' || $bankAccount !== '') {
                $bankBits = array_filter([$bankName, $bankAccount, $bankHolder]);
                $lines[] = '• *Bank*: ' . implode(' — ', $bankBits);
            }
        }

        if (empty($lines)) {
            return $intro . "\n\n"
                . "Bado hakuna njia za malipo zilizowekwa.\n"
                . "Andika */support* kupata maelezo ya malipo kutoka kwa mhudumu.\n\n"
                . $footer;
        }

        $msg = $intro . "\n\n";
        foreach ($lines as $i => $line) {
            $msg .= ($i + 1) . ". " . ltrim($line, "• ") . "\n";
        }
        $msg .= "\n" . $footer;

        return $msg;
    }
}
