<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\SystemSetting;
use App\Services\SmsService;
use App\Models\SmsLog;
use Illuminate\Console\Command;

class SendFollowupReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followups:send-reminders {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends automated follow-up reminders to customers scheduled for today';

    /**
     * Execute the console command.
     */
    public function handle(SmsService $sms, \App\Services\WhatsAppService $whatsapp)
    {
        $settings = SystemSetting::pluck('value', 'key');

        // Check if automation is enabled
        if (($settings['followup_reminder_enabled'] ?? '0') !== '1') {
            $this->info('Follow-up reminder automation is disabled.');
            return;
        }

        $preferredTime = $settings['followup_reminder_time'] ?? '08:30';
        $currentTime = now()->format('H:i');

        if ($currentTime < $preferredTime && !$this->option('force')) {
            $this->info("Schedule not met. (Current: {$currentTime}, Preferred: {$preferredTime})");
            return;
        }

        // Fetch customers with follow-up scheduled for today
        $customers = Customer::whereDate('next_follow_up_date', now()->toDateString())
            ->where('is_draft', false)
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No follow-ups scheduled for today.');
            return;
        }

        $template = $settings['followup_reminder_template'] ?? 'Habari {name}, TRUMARK tunapenda kukukumbusha kuhusu huduma tulizozungumzia. Je, una maswali yoyote? Karibu!';
        $useWhatsapp = ($settings['survey_channels_whatsapp'] ?? '0') === '1'; // Using the global WA toggle

        $this->info("Sending " . $customers->count() . " reminders...");

        foreach ($customers as $customer) {
            $message = str_replace('{name}', $customer->name, $template);
            
            // 1. Send SMS
            $result = $sms->sendSms($customer->phone, $message);
            SmsLog::create([
                'customer_id' => $customer->id,
                'phone'       => $customer->phone,
                'message'     => $message,
                'status'      => $result['success'] ? 'sent' : 'failed',
                'response'    => $result['response'] ?? null,
            ]);

            // 2. Send WhatsApp (Using template if configured, otherwise standard)
            if ($useWhatsapp && $customer->phone) {
                $waTemplate = $settings['wa_template_followup_name'] ?? 'follow_up_reminder';
                $waLang = $settings['wa_template_followup_lang'] ?? 'en';
                
                $waResult = $whatsapp->sendTemplateMessage($customer->phone, $waTemplate, $waLang, [$customer->name]);
                
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'phone'       => $customer->phone,
                    'message'     => "[WhatsApp Template: {$waTemplate}] " . $message,
                    'status'      => $waResult['success'] ? 'sent' : 'failed',
                    'response'    => $waResult['response'] ?? null,
                ]);
            }

            $this->info("Sent to {$customer->name}");
        }

        $this->info('Follow-up reminder dispatch completed.');
    }

    /**
     * Add a force option for testing
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', null, 'Force the command to run regardless of schedule'],
        ];
    }
}
