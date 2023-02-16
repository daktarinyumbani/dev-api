<?php

namespace App\Channels;

use AfricasTalking\SDK\AfricasTalking;
use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            $message = $notification->toPushNotification($notifiable);

            $params = [
                'token' => $message['token'],
                'title' => $message['title'],
                'message' => $message['body']
            ];

            $response = Http::post(env('NOTIFICATIONS_API_URL', ''), $params);

            if($response->status() == 200) {
                Log::debug('Push notification send successfully');
            } else {
                Log::error($response->body());
            }

        } catch (\Exception $exception) {
            Log::error('Something went wrong sending push notification');
            Log::error($exception->getMessage());
        }
    }
}
