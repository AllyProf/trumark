<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\SystemSetting;
use App\Services\SmsService;
use App\Mail\SurveyInvitation;
use App\Models\SmsLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAutomatedSurveys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surveys:send-automated {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends automated survey invitations to customers based on system settings';

    /**
     * Execute the console command.
     */
    public function handle(SmsService $sms, \App\Services\WhatsAppService $whatsapp)
    {
        $settings = SystemSetting::pluck('value', 'key');

        // Check if automation is enabled
        if (($settings['survey_enabled'] ?? '0') !== '1') {
            $this->info('Survey automation is disabled.');
            return;
        }

        $frequency = $settings['survey_frequency'] ?? 'weekly';
        $preferredTime = $settings['survey_time'] ?? '09:00';
        $today = now();

        // Check if it's the right day and time to send
        $isCorrectDay = false;
        if ($frequency === 'weekly') {
            $targetDay = $settings['survey_day_weekly'] ?? 'Monday';
            if ($today->format('l') === $targetDay) {
                $isCorrectDay = true;
            }
        } elseif ($frequency === 'monthly') {
            $targetDay = $settings['survey_day_monthly'] ?? '1';
            if ($targetDay === 'first') {
                if ($today->day === 1) $isCorrectDay = true;
            } elseif ($targetDay === 'last') {
                if ($today->day === $today->daysInMonth) $isCorrectDay = true;
            } else {
                if ($today->day === (int)$targetDay) $isCorrectDay = true;
            }
        } elseif ($frequency === 'specific') {
            $targetDate = $settings['survey_specific_date'] ?? '';
            if ($today->toDateString() === $targetDate) {
                $isCorrectDay = true;
            }
        }

        // Check time (Allow a 1-hour window if running via cron)
        $isCorrectTime = false;
        $currentTime = $today->format('H:i');
        if ($currentTime >= $preferredTime) {
            $isCorrectTime = true;
        }

        if ((!$isCorrectDay || !$isCorrectTime) && !$this->option('force')) {
            $this->info("Schedule not met. (Day: " . ($isCorrectDay ? 'Yes' : 'No') . ", Time: " . ($isCorrectTime ? 'Yes' : 'No') . ")");
            return;
        }

        // Channels
        $useSms = ($settings['survey_channels_sms'] ?? '1') === '1';
        $useEmail = ($settings['survey_channels_email'] ?? '1') === '1';
        $useWhatsapp = ($settings['survey_channels_whatsapp'] ?? '0') === '1';

        // Fetch customers who haven't been surveyed in the last 30 days (to avoid spam)
        $customers = Customer::where(function($q) {
            $q->whereNull('last_survey_sent_at')
              ->orWhere('last_survey_sent_at', '<', now()->subDays(30));
        })
        ->where('is_draft', false)
        ->get();

        $this->info("Found " . $customers->count() . " customers to survey.");

        $smsTemplate = $settings['survey_sms_template'] ?? 'Habari {name}, asante kwa kuchagua TRUMARK. Tafadhali tufahamishe jinsi ulivyohudumiwa hapa: {link}. Asante!';

        foreach ($customers as $customer) {
            $url = route('feedback.show', $customer->survey_uuid);
            
            // 1. Send SMS
            if ($useSms && $customer->phone) {
                $msg = str_replace(['{name}', '{link}'], [$customer->name, $url], $smsTemplate);
                $result = $sms->sendSms($customer->phone, $msg);
                
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'phone'       => $customer->phone,
                    'message'     => $msg,
                    'status'      => $result['success'] ? 'sent' : 'failed',
                    'response'    => $result['response'] ?? null,
                ]);
            }

            // 2. Send WhatsApp
            if ($useWhatsapp && $customer->phone) {
                $templateName = $settings['wa_template_survey_name'] ?? 'survey_invitation';
                $languageCode = $settings['wa_template_survey_lang'] ?? 'en';
                
                // Parameters for the template: {{1}} is Name, {{2}} is URL
                $result = $whatsapp->sendTemplateMessage(
                    $customer->phone, 
                    $templateName, 
                    $languageCode, 
                    [$customer->name, $url]
                );
                
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'phone'       => $customer->phone,
                    'message'     => "[WhatsApp Template: {$templateName}] " . $url,
                    'status'      => $result['success'] ? 'sent' : 'failed',
                    'response'    => $result['response'] ?? null,
                ]);
            }

            // 3. Send Email
            if ($useEmail && $customer->email) {
                try {
                    Mail::to($customer->email)->send(new SurveyInvitation($customer));
                } catch (\Exception $e) {
                    $this->error("Failed to email {$customer->email}: " . $e->getMessage());
                }
            }

            // Update timestamp
            $customer->update(['last_survey_sent_at' => now()]);
            $this->info("Sent to {$customer->name}");
        }

        $this->info('Automated survey dispatch completed.');
    }

    /**
     * Add a force option for testing
     */
}
