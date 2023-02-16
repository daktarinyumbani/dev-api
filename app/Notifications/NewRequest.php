<?php

namespace App\Notifications;

use App\Channels\PushNotificationChannel;
use App\Channels\SMSChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewRequest extends Notification implements ShouldQueue
{
    use Queueable;

    private $requester;

    /**
     * Create a new notification instance.
     *
     * @param $requester
     */
    public function __construct($requester)
    {
        //
        $this->requester = $requester;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [PushNotificationChannel::class, SMSChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toSMS($notifiable)
    {
        $requesterPhone = $this->requester->phone ? $this->requester->phone : 'N/A';
        return [
            "phone" => $notifiable->phone,
            "message" => "A new request has been created requiring your attention. Client phone: " . $requesterPhone
        ];
    }

    /**
     * Get the PUSH notification representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toPushNotification($notifiable)
    {
        return [
            "token" => $notifiable->device_token,
            "title" => "Services Requested",
            "body" => "A new request has been created requiring your attention."
        ];
    }


}
