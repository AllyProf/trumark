<?php

namespace App\Notifications;

use App\Models\Customer;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

use Illuminate\Queue\SerializesModels;

class LeadAssignedSmsNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $customer;

    /**
     * Create a new notification instance.
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        Log::info("🔔 Processing LeadAssignedSmsNotification for {$notifiable->name}...");

        // Trigger SMS and WhatsApp manually if the user has a phone
        if ($notifiable->phone) {
            if (\App\Models\SystemSetting::isSmsEnabled('lead_assignment')) {
                $this->sendSms($notifiable);
            } else {
                Log::info("⏭️ Skipping assignment SMS for {$notifiable->name}: SMS channel disabled for lead assignment.");
            }
            $this->sendWhatsApp($notifiable);
        } else {
            Log::warning("⚠️ Skipping SMS/WhatsApp for {$notifiable->name}: No phone number.");
        }

        return [
            'customer_id'   => $this->customer->id,
            'customer_name' => $this->customer->name,
            'branch_id'     => (int) $this->customer->branch_id,
            'message'       => "New lead assigned: {$this->customer->name}",
            'action_url'    => route('customers.show', $this->customer->id, false),
            'type'          => 'lead_assigned',
        ];
    }

    /**
     * Send SMS via SmsService
     */
    protected function sendSms($notifiable)
    {
        try {
            $smsService = new SmsService();
            $message = "Habari {$notifiable->name}, umepangiwa mteja mpya: {$this->customer->name}. Tafadhali ingia kwenye CRM kufuatilia.";
            $smsService->sendSms($notifiable->phone, $message);
        } catch (\Exception $e) {
            Log::error("Failed to send assignment SMS: " . $e->getMessage());
        }
    }

    /**
     * Send WhatsApp via WhatsAppService
     */
    protected function sendWhatsApp($notifiable)
    {
        try {
            $waService = new WhatsAppService();
            
            // Build the alert message content
            $alertContent = "Umepangiwa mteja mpya: {$this->customer->name}. Fuatilia kupitia CRM mfumo.";
            
            // Send using the verified Meta template to ensure delivery
            $waService->sendTemplateMessage($notifiable->phone, 'general_broadcast', 'en', [
                'customer_name' => $notifiable->name,
                'message_content' => $alertContent
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send assignment WhatsApp: " . $e->getMessage());
        }
    }
}
