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
     * Build WhatsApp bot payment instructions from admin-configured numbers.
     */
    public static function whatsappPaymentMessage(): string
    {
        $mpesa = trim(self::get('payment_mpesa', ''));
        $tigo = trim(self::get('payment_tigo', ''));
        $airtel = trim(self::get('payment_airtel', ''));
        $bankName = trim(self::get('payment_bank_name', ''));
        $bankAccount = trim(self::get('payment_bank_account', ''));
        $bankHolder = trim(self::get('payment_bank_holder', ''));

        $msg = "💳 *NJIA ZA MALIPO / PAYMENT METHODS*\n\n";
        $msg .= "1. 💵 *Cash*: Lipa moja kwa moja Ubungo EACLC au Kimara Stopover.\n\n";

        if ($mpesa !== '') {
            $msg .= "2. 📱 *M-Pesa*: {$mpesa}\n\n";
        } else {
            $msg .= "2. 📱 *M-Pesa*: Andika /support kupata namba.\n\n";
        }

        if ($tigo !== '') {
            $msg .= "3. 📱 *Tigo Pesa*: {$tigo}\n\n";
        } else {
            $msg .= "3. 📱 *Tigo Pesa*: Andika /support kupata namba.\n\n";
        }

        if ($airtel !== '') {
            $msg .= "4. 📱 *Airtel Money*: {$airtel}\n\n";
        } else {
            $msg .= "4. 📱 *Airtel Money*: Andika /support kupata namba.\n\n";
        }

        if ($bankName !== '' && $bankAccount !== '') {
            $msg .= "5. 🏦 *Bank Transfer*:\n";
            $msg .= "   Benki: {$bankName}\n";
            $msg .= "   Akaunti: {$bankAccount}\n";
            if ($bankHolder !== '') {
                $msg .= "   Jina: {$bankHolder}\n";
            }
            $msg .= "\n";
        } else {
            $msg .= "5. 🏦 *Bank Transfer*: Andika /support kupata maelezo ya benki.\n\n";
        }

        $msg .= "✅ Baada ya kulipa, *tuma picha ya muamala (screenshot)* hapa ili tuthibitishe na kuanza mzigo wako mara moja!";

        return $msg;
    }
}
