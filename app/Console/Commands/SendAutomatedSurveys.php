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

        // Determine dynamic day limits based on selected schedule frequency to avoid duplicate spamming
        $daysLimit = 7;
        if ($frequency === 'monthly') {
            $daysLimit = 25;
        } elseif ($frequency === 'specific') {
            $daysLimit = 1;
        }

        // Fetch customers who haven't been surveyed within the dynamic days limit
        $customers = Customer::where(function($q) use ($daysLimit) {
            $q->whereNull('last_survey_sent_at')
              ->orWhere('last_survey_sent_at', '<', now()->subDays($daysLimit));
        })
        ->where('is_draft', false)
        ->get();

        $this->info("Found " . $customers->count() . " customers to survey.");

        $smsTemplate = $settings['survey_sms_template'] ?? 'Habari {name}, asante kwa kuchagua TRUMARK. Tafadhali tufahamishe jinsi ulivyohudumiwa hapa: {link}. Asante!';

        foreach ($customers as $customer) {
            if (!$customer->survey_uuid) {
                $customer->survey_uuid = (string) \Illuminate\Support\Str::uuid();
                $customer->save();
            }

            // Use production domain for all links
            $url = 'https://trumark.mauzolink.co.tz/feedback/' . $customer->survey_uuid;
            $msg = str_replace(['{name}', '{link}'], [$customer->name, " " . $url . " "], $smsTemplate);
            $msg .= "\n\n(Note: Save our contact to make the link clickable! 🙏)";
            
            // 1. Send SMS
            if ($useSms && $customer->phone) {
                $result = $sms->sendSms($customer->phone, $msg);
                
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'phone'       => $customer->phone,
                    'message'     => "[Automated SMS] " . $msg,
                    'status'      => $result['success'] ? 'sent' : 'failed',
                    'response'    => isset($result['response']) ? (is_array($result['response']) ? json_encode($result['response']) : $result['response']) : null,
                ]);
            }

            // 2. Send WhatsApp (Template)
            if ($useWhatsapp && $customer->phone) {
                $templateName = $settings['wa_template_survey_name'] ?? 'survey_invitation';
                $languageCode = $settings['wa_template_survey_lang'] ?? 'en';
                
                $cleanMsg = preg_replace('/\s+/', ' ', $msg);
                
                $waParams = ['customer_name' => $customer->name];
                $buttonParams = [];

                if ($templateName === 'survey_invitation') {
                    // Only pass UUID for the button
                    $buttonParams = [$customer->survey_uuid];
                } else {
                    $waParams['message_content'] = $cleanMsg;
                }

                $result = $whatsapp->sendTemplateMessage($customer->phone, $templateName, $languageCode, $waParams, $buttonParams);
                
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'phone'       => $customer->phone,
                    'message'     => "[Automated WhatsApp: {$templateName}] " . $msg,
                    'status'      => $result['success'] ? 'sent' : 'failed',
                    'response'    => isset($result['response']) ? (is_array($result['response']) ? json_encode($result['response']) : $result['response']) : null,
                ]);
            }

            // 3. Send Email
            if ($useEmail && $customer->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($customer->email)->send(new \App\Mail\CustomerReminderMail($customer, $msg));
                    
                    SmsLog::create([
                        'customer_id' => $customer->id,
                        'phone'       => $customer->phone,
                        'message'     => "[Automated Email Survey] " . $msg,
                        'status'      => 'sent',
                        'response'    => 'Email dispatched successfully via SMTP',
                    ]);
                } catch (\Exception $e) {
                    $this->error("Failed to email {$customer->email}: " . $e->getMessage());
                    SmsLog::create([
                        'customer_id' => $customer->id,
                        'phone'       => $customer->phone,
                        'message'     => "[Automated Email Survey FAILED] " . $msg,
                        'status'      => 'failed',
                        'response'    => $e->getMessage(),
                    ]);
                }
            }

            // Update timestamp
            $customer->update(['last_survey_sent_at' => now()]);
            $this->info("Successfully dispatched survey to: {$customer->name}");
        }

        $this->info('🚀 Automated multi-channel survey dispatch completed!');
    }

    /**
     * Add a force option for testing
     */
}
