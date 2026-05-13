<?php

namespace App\Notifications;

use App\Models\KpiActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class ComplaintAlertNotification extends Notification
{
    use Queueable;

    protected $activity;

    /**
     * Create a new notification instance.
     */
    public function __construct(KpiActivity $activity)
    {
        $this->activity = $activity;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $officer = $this->activity->user->name;
        $customer = $this->activity->customer ? $this->activity->customer->name : 'Unknown Customer';
        
        return [
            'title'   => '⚠️ Critical Customer Complaint',
            'message' => "A customer ({$customer}) has submitted negative feedback for officer {$officer}.",
            'link'    => route('kpi.activities', ['user_id' => $this->activity->user_id]),
            'type'    => 'customer_complaint',
            'icon'    => 'fa-exclamation-circle',
            'color'   => 'text-danger'
        ];
    }
}
