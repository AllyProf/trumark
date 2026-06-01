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

        // Check if reminders were already sent to prevent duplicates when running every minute
        $alreadySentIds = SmsLog::whereDate('created_at', now()->toDateString())
            ->where(function($q) {
                $q->where('message', 'like', '[Automated SMS Followup]%')
                  ->orWhere('message', 'like', '[Automated WhatsApp Followup]%');
            })
            ->pluck('customer_id')
            ->toArray();

        // Fetch customers with follow-up scheduled for today who haven't received a reminder today
        $customers = Customer::whereDate('next_follow_up_date', now()->toDateString())
            ->whereNotIn('id', $alreadySentIds)
            ->where('is_draft', false)
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No unsent follow-ups scheduled for today.');
            return;
        }

        $template = $settings['followup_reminder_template'] ?? 'Habari {name}, TRUMARK tunapenda kukukumbusha kuhusu huduma tulizozungumzia. Je, una maswali yoyote? Karibu!';
        $useSms = ($settings['followup_channels_sms'] ?? '1') === '1';
        $useWhatsapp = ($settings['followup_channels_whatsapp'] ?? '0') === '1';
        $useEmail = ($settings['followup_channels_email'] ?? '1') === '1';

        if (!$useSms && !$useWhatsapp && !$useEmail) {
            $this->info('No follow-up reminder channels are enabled.');
            return;
        }

        $this->info("Sending " . $customers->count() . " reminders...");

        foreach ($customers as $customer) {
            $message = str_replace('{name}', $customer->name, $template);

            if ($useSms && $customer->phone) {
                $result = $sms->sendSms($customer->phone, $message);
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'phone'       => $customer->phone,
                    'message'     => "[Automated SMS Followup] " . $message,
                    'status'      => $result['success'] ? 'sent' : 'failed',
                    'response'    => isset($result['response']) ? (is_array($result['response']) ? json_encode($result['response']) : $result['response']) : null,
                ]);
            }

            if ($useWhatsapp && $customer->phone) {
                $waTemplate = $settings['wa_template_followup_name'] ?? 'follow_up_reminder';
                $waLang = $settings['wa_template_followup_lang'] ?? 'en';
                
                $waResult = $whatsapp->sendTemplateMessage($customer->phone, $waTemplate, $waLang, [
                    'customer_name' => $customer->name
                ]);
                
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'phone'       => $customer->phone,
                    'message'     => "[Automated WhatsApp Followup: {$waTemplate}] " . $message,
                    'status'      => $waResult['success'] ? 'sent' : 'failed',
                    'response'    => isset($waResult['response']) ? (is_array($waResult['response']) ? json_encode($waResult['response']) : $waResult['response']) : null,
                ]);
            }

            // 3. Send Email
            if ($useEmail && $customer->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($customer->email)->send(new \App\Mail\CustomerReminderMail($customer, $message));
                } catch (\Exception $e) {
                    $this->error("Failed to email {$customer->email}: " . $e->getMessage());
                }
            }

            // Notify the assigned sales officer on enabled channels
            if ($customer->salesOfficer) {
                $officer = $customer->salesOfficer;
                $officerMessage = "TRUMARK Reminder: You have a scheduled follow-up with {$customer->name} today. Please contact them.";

                if ($useSms && $officer->phone) {
                    $sms->sendSms($officer->phone, $officerMessage);
                }

                if ($useWhatsapp && $officer->phone) {
                    $whatsapp->sendMessage($officer->phone, $officerMessage);
                }

                if ($useEmail && $officer->email) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw($officerMessage, function($m) use ($officer, $customer) {
                            $m->to($officer->email)->subject("Action Required: Follow-up with {$customer->name}");
                        });
                    } catch (\Exception $e) {
                        $this->error("Failed to email officer {$officer->email}: " . $e->getMessage());
                    }
                }
            }

            $this->info("Successfully dispatched follow-up to: {$customer->name}");
        }

        $this->info('🚀 Multi-channel follow-up reminder dispatch completed!');
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
