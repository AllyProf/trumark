<?php

namespace App\Notifications;

use App\Models\KpiActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class KpiAdjustmentNotification extends Notification
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
        $points = $this->activity->points;
        $label = $points >= 0 ? "Awarded (+{$points})" : "Deducted ({$points})";
        
        return [
            'title'   => 'KPI Points Adjusted',
            'message' => "A manager has {$label} points for: {$this->activity->description}",
            'link'    => route('kpi.leaderboard'),
            'type'    => 'kpi_adjustment',
            'icon'    => $points >= 0 ? 'fa-plus-circle' : 'fa-minus-circle',
            'color'   => $points >= 0 ? 'text-success' : 'text-danger'
        ];
    }
}
