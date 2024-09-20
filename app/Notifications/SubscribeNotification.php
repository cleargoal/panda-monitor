<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubscribeNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;

    private array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
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
        $urlDelete = route('adverts.destroy', ['advert' => $this->data['advertId']]); // URL for delete/unsubscribe action
        $sourceUrl = $this->data['sourceUrl'];
        $name = $this->data['name'];
        return (new MailMessage)
            ->subject('Successful subscription')
            ->greeting('Subscription Successful!')
            ->line('You are successfully subscribed to the advert:')
            ->line($name)
            ->line('URL: ' . $sourceUrl)
            ->line('<br>')
            ->action('You can delete this subscription', $urlDelete)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
