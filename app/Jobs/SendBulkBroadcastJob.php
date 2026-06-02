<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendBulkBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customerIds;
    protected $message;
    protected $channels;
    protected $senderId;

    public function __construct($customerIds, $message, $channels, $senderId)
    {
        $this->customerIds = $customerIds;
        $this->message = $message;
        $this->channels = $channels;
        $this->senderId = $senderId;
    }

    public function handle()
    {
        $sms = app(\App\Services\SmsService::class);
        $whatsapp = app(\App\Services\WhatsAppService::class);

        foreach ($this->customerIds as $id) {
            $customer = Customer::find($id);
            if (!$customer) continue;

            $message = str_replace('{name}', $customer->name, $this->message);

            // 1. SMS
            if (in_array('sms', $this->channels)) {
                $result = $sms->sendSms($customer->phone, $message);
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'sender_id'   => $this->senderId,
                    'phone'       => $customer->phone,
                    'message'     => "[SMS Broadcast] " . $message,
                    'status'      => $result['success'] ? 'sent' : 'failed',
                    'response'    => isset($result['response']) ? json_encode($result['response']) : null,
                ]);
            }

            if (in_array('whatsapp', $this->channels) && $customer->phone) {
                $waTemplate = SystemSetting::where('key', 'whatsapp_template_general')->first()?->value ?? 'general_broadcast';
                $cleanMsg = preg_replace('/\s+/', ' ', $message);

                $result = $whatsapp->sendTemplateMessage($customer->phone, $waTemplate, 'en', [
                    'customer_name' => $customer->name,
                    'message_content' => $cleanMsg,
                ]);

                $wamid = $result['response']['messages'][0]['id'] ?? null;

                SmsLog::create([
                    'customer_id'         => $customer->id,
                    'sender_id'           => $this->senderId,
                    'phone'               => $customer->phone,
                    'message'             => "[WhatsApp Broadcast: {$waTemplate}] " . $message,
                    'status'              => $result['success'] ? 'sent' : 'failed',
                    'response'            => \App\Services\WhatsAppService::logResponseFromResult($result),
                    'whatsapp_message_id' => $wamid,
                ]);
            }

            // 3. Email
            if (in_array('email', $this->channels) && $customer->email) {
                try {
                    Mail::to($customer->email)->send(new \App\Mail\CustomerReminderMail($customer, $message));
                } catch (\Exception $e) {
                    Log::error("Bulk Email failed for {$customer->email}: " . $e->getMessage());
                }
            }
        }
    }
}
