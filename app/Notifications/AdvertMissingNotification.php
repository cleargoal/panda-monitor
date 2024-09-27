<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdvertMissingNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;

    private string $subject;
    private string $sourceUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $subject, string $sourceUrl)
    {
        $this->subject = $subject;
        $this->sourceUrl = $sourceUrl;
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
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Advert missing or deactivated.')
            ->line('URL: ' . $this->sourceUrl)
            ->line('Thank you for using our application!');
    }

}
