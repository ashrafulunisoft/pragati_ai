<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\SmsNotificationService;

class VisitorRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    protected $visitor;
    protected $visit;

    /**
     * Create a new notification instance.
     */
    public function __construct($visitor, $visit)
    {
        $this->visitor = $visitor;
        $this->visit = $visit;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $visitDate = \Carbon\Carbon::parse($this->visit->schedule_time)->format('F j, Y - g:i A');
        $visitType = $this->visit->type ? $this->visit->type->name : 'N/A';

        return (new MailMessage)
            ->subject('🎉 Visitor Registration Confirmation - UCB Bank')
            ->greeting('Dear ' . $this->visitor->name . ',')
            ->line('Thank you for registering your visit to UCB Bank.')
            ->line('Your visit details are as follows:')
            ->line('📅 **Visit Date:** ' . $visitDate)
            ->line('👤 **Visitor Name:** ' . $this->visitor->name)
            ->line('📧 **Email:** ' . $this->visitor->email)
            ->line('📱 **Phone:** ' . ($this->visitor->phone ?? 'N/A'))
            ->line('🏢 **Company:** ' . ($this->visitor->address ?? 'N/A'))
            ->line('👔 **Visit Type:** ' . $visitType)
            ->line('🎯 **Purpose:** ' . $this->visit->purpose)
            ->line('📊 **Status:** ' . ucfirst($this->visit->status))
            ->line('')
            ->line('Please arrive 10-15 minutes before your scheduled time.')
            ->line('If you need to reschedule, please contact us.')
            ->action('View Visit Details', url('/admin/visitor/list'))
            ->line('')
            ->line('This is an automated message. Please do not reply.')
            ->salutation('Best regards,')
            ->salutation('UCB Bank Visitor Management Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'visitor_id' => $this->visitor->id,
            'visit_id' => $this->visit->id,
            'visitor_name' => $this->visitor->name,
            'visit_date' => $this->visit->schedule_time,
            'status' => $this->visit->status,
        ];
    }
}
