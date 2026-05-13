<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowUpReminderNotification extends Notification
{
    use Queueable;

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
        return [
            'customer_id'   => $this->customer->id,
            'customer_name' => $this->customer->name,
            'branch_id'     => (int) $this->customer->branch_id,
            'message'       => "Follow-up due today for {$this->customer->name}",
            'action_url'    => route('customers.show', $this->customer->id, false),
            'type'          => 'follow_up_reminder',
        ];
    }
}
