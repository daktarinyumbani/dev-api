<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\Business;
use App\Notifications\PasswordResetRequested;
use App\Notifications\UserCreated;
use App\Traits\Users;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use Users;
    public function register(Request $request)
    {
        
   
      
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|min:3',
                'last_name' => 'required|min:3',
                'phone' => 'required|unique:users',
                'password' => 'required|min:8',
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'status' => false,
                    'error' => $validator->errors()->first()
                ], 200);

            } else {
                $user = $this->getOrCreateUser($request->all(), true);

                if ($user) {
               

                    $business_type=$request->account;
                     if($business_type !='patient'){
                        Business::create([
                         'name'=>strtoupper($request->business_name),
                         'type'=>$business_type,
                         'phone'=>$request->phone,
                         'doc_url'=>$request->doc_url,
                         'active'=>0,
                         'business_type'=>$business_type,
                         'user_id'=>$user->id
                        ]);
                     }

                     return response()->json([
                        'status' => true,
                        'message' => 'User Account created successfully',
                        'user' => User::find($user->id),
                        'config' => $this->getConfiguration()
                    ], 200);
                } else {
                    return response()->json([
                        'status' => false,
                        'error' => 'Registration failed'
                    ], 200);
                }
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return response()->json([
                'status' => false,
                'error' => $exception->getMessage(),
                'message' => 'Registration failed',
            ], 200);
        }
    }

    public function loginByEmail(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $validator = Validator::make($credentials, [
            'email' => 'email|required',
            'password' => 'required'
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 401);
        } else {
            if (Auth::attempt($credentials)) {
//                return response()->make(Auth::user());
                $user = Auth::user();
                $token =  $user->createToken(config('app.name'));
                return response()->json([
                    'status' => true,
                    'user' => [
                        'user' => $user,
                        'token' => $token
                    ],
                    'config' => $this->getConfiguration()
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'error' => 'Please check your credentials'
                ], 401);
            }
        }
    }

    public function loginByPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 401);
        } else {
            if (Auth::attempt(['phone' => request('phone'), 'password' => request('password')])) {
                $user = User::with('roles', 'serviceProvider.specialty', 'ambulance','business')->find(Auth::id());
                $token =  $user->createToken($user->id)->plainTextToken;
                return response()->json([
                    'status' => true,
                    'token' => $token,
                    'user' => $user,
                    'config' => $this->getConfiguration()
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'error' => 'Please check your credentials'
                ], 401);
            }
        }
    }

    public function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 200);
        } else {
            
            $user = User::find($request->user_id);
            if (Hash::check($request->code, $user->otp)) {
                //success
                $user->phone_verified_at = Carbon::now();
                $user->active = true;
                $user->otp = null;

                $token =  $user->createToken($user->id)->plainTextToken;

                if($user->save()) {
                    return response()->json([
                        'status' => true,
                        'token' => $token,
                        'message' => 'Verified successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => false,
                        'error' => 'Failed to verify, system error.',
                        'message' => 'System error'
                    ], 500);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'error' => 'Please check the verification code.',
                    'message' => 'Please check the verification code.'
                ], 401);
            }
        }
    }


    public function forgotPassword(Request $request)
    
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required_without:email',
            'email' => 'required_without:phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 200);
        } else {
            //get the user
            if($request->has('email')) {
                $user = User::where('email', '=', $request->email)->first();
            } else {
                $user = User::where('phone', '=', $request->phone)->first();
            }

            if ($user===null) {
                return response()->json([
                    'status' => false,
                    'error' => 'User not found'
                ], 200);
            }

            //if we have the user, let's create the reset record using their email address
            //but first lets create a new OTP
            $otp = mt_rand(100000, 999999);

            DB::table('password_resets')
                ->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($otp)]
                );

            $user->notify(new PasswordResetRequested($user, $otp));

            return response()->json([
                'status' => true,
                'message' => 'Password reset initiated.',
                'user' => $user,
                'config' => $this->getConfiguration()
            ], 200);
        }

    }

    public function resetPassword(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required_without:email|exists:users,phone',
                'email' => 'required_without:phone|exists:users,email',
                'code' => 'required',
                'password' => 'required|min:8'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors()->first()
                ], 200);
            } else {
                //get the user
                //get the user
                if($request->has('email')) {
                    $user = User::where('email', '=', $request->email)->first();
                } else {
                    $user = User::where('phone', '=', $request->phone)->first();
                }
                if ($user === null) {
                    return response()->json([
                        'status' => false,
                        'error' => 'User not found'
                    ], 200);
                }

                $passwordResetRecord = DB::table('password_resets')->where('email', '=', $user->email)->first();

                if (Hash::check($request->code, $passwordResetRecord->token)) {
                    //then it's validated
                    $user->password = Hash::make($request->password);

                    //remove the reset record
                    DB::table('password_resets')
                        ->where(
                            ['email' => $user->email],
                            ['token' => Hash::make($request->code)]
                        )->delete();

                    if ($user->save()) {
                        return response()->json([
                            'status' => true,
                            'message' => 'Password reset successfully',
                            'user' => $user,
                            'config' => $this->getConfiguration()
                        ], 200);
                    }
                }

            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return response()->json([
                'status' => false,
                'error' => 'Something went wrong.',
                'message' => 'Something went wrong'
            ], 401);
        }

        return response()->json([
            'status' => false,
            'error' => 'Something went wrong.',
            'message' => 'Something went wrong'
        ], 401);
    }

    public function setDeviceToken(Request $request) {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        try {

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors()->first()
                ], 200);
            } else {
                //get the user
                $user = User::findOrFail(Auth::id());
                $user->device_token = $request->token;

                if ($user->save()) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Token set successfully'
                    ], 200);
                }
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return response()->json([
                'status' => false,
                'error' => $exception->getMessage(),
                'message' => 'Please check your request.'
            ], 500);
        }
        return response()->json([
            'status' => false,
            'error' => 'Please check your request.',
            'message' => 'Please check your request.'
        ], 500);
    }

    public function updateServiceProviderProfile(Request $request) {

        try {
            $updateData = $request->all();
            $user = User::with('roles', 'serviceProvider.specialty', 'ambulance')->findOrFail(Auth::id());

            if($user->serviceProvider()->exists()) {
                $user->serviceProvider()->update($updateData);
            }

            if($user->ambulance()->exists()) {
                $user->ambulance()->update($updateData);
            }

            $user->refresh();

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, 'Something went wrong', []);
        }

        return $this->returnJsonResponse(
            true, 'Updated', ['user' => $user]
        );
    }

    public function getConfiguration() {
        return [
            'payment_methods' => [
                [
                    'name' => 'Pay with selcom',
                    'number' => '60627833',
                    'image' => '/logos/selcom.png',
                    'instructions' =>
                        '<h1>DIAL *150*50*1#</h1>
                        <ol>
                        <li>Enter Pay Number (60627833)</li>
                        <li>Enter Amount</li>
                        <li>Confirm Payment</li>
                        <li>Enter PIN</li>
                        <li>Complete Payment</li>
                        </ol>'
                ]
            ]
        ];
    }

//    public function updateProfile(Request $request) {
//
//        try {
//
//            $updateData = $request->all();
//            if($request->has('password')) {
//                $updateData['password'] = Hash::make($request->get('password'));
//            }
//
//            $user = User::with('serviceProvider.specialty')->findOrFail(Auth::id());
//            $user->update($updateData);
//        } catch (\Exception $exception) {
//            Log::error($exception->getMessage());
//            return $this->returnJsonResponse(false, 'Something went wrong', []);
//        }
//
//        return $this->returnJsonResponse(
//            true, 'Updated', ['user' => $user]
//        );
//    }
}
