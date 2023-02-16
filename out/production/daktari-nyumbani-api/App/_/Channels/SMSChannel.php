<?php

namespace App\Channels;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SMSChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            $message = $notification->toSMS($notifiable);

            Log::info('SMS NOTIFICATION: ' . json_encode($message));
            // Send notification to the $notifiable instance...
            //$username = "sandbox";
            $username = env('AT_USERNAME', false);
            $apiKey = env('AT_KEY', false);
            $AT = new AfricasTalking($username, $apiKey);
            $sms = $AT->sms();

            $result = $sms->send([
                'to' => $message['phone'],
                'message' => $message['message']
            ]);
            $resultString = json_encode($result);
            Log::info('SMS NOTIFICATION: ' . $resultString);
        } catch (\Exception $exception) {
            Log::error('FAIL SMS NOTIFICATION: ' . $exception->getMessage());
        }

    }
}
