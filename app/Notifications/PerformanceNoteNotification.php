<?php

namespace App\Notifications;

use App\Models\KpiNote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PerformanceNoteNotification extends Notification
{
    use Queueable;

    protected $note;

    /**
     * Create a new notification instance.
     */
    public function __construct(KpiNote $note)
    {
        $this->note = $note;
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
        $managerName = $this->note->manager->name ?? 'A Manager';
        return [
            'title'   => 'New Performance Note',
            'message' => "{$managerName} added a new coaching note: \"".substr($this->note->note, 0, 50)."...\"",
            'link'    => route('kpi.leaderboard'), // Officers are redirected to their status page
            'type'    => 'kpi_note',
            'icon'    => 'fa-pencil-square',
            'color'   => 'text-danger'
        ];
    }
}
