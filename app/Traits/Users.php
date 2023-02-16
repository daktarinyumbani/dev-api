<?php

namespace App\Traits;

use App\Models\User;
use App\Notifications\UserCreated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

trait Users
{
    public function getOrCreateUser($userDetails, $notify = false) {
        try {
            $otp = mt_rand(100000, 999999);
            $userDetails['otp'] = Hash::make($otp);
            $userDetails['password'] = Hash::make($userDetails['password']);
            $userDetails['email'] = $userDetails['phone'] . '@' . env('daktari-nyumbani-api.test', 'daktarinyumbani.co.tz');

            $user = User::firstOrCreate(
                ["phone" => $userDetails['phone']],
                $userDetails
            );

            $notify ? $user->notify(new UserCreated($user, $otp)) : null;

            return $user;

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return null;
        }
    }
}
