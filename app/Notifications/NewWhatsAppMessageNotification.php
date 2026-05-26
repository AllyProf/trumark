<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewWhatsAppMessageNotification extends Notification
{
    use Queueable;

    protected $phone;
    protected $customerName;
    protected $messagePreview;

    public function __construct($phone, $customerName, $messagePreview)
    {
        $this->phone          = $phone;
        $this->customerName   = $customerName;
        $this->messagePreview = $messagePreview;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title'      => '💬 WhatsApp: ' . $this->customerName,
            'message'    => \Illuminate\Support\Str::limit($this->messagePreview, 80),
            'icon'       => 'fa-whatsapp',
            'color'      => 'text-success',
            'link'       => '/whatsapp/chat',
            'action_url' => '/whatsapp/chat',
        ];
    }
}
