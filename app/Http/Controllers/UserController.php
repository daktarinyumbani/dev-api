<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use FileUpload;
    //
    public function index() {
        try {

            $users = User::withCount('requests')->get();

            $data = [
                'users' => $users
            ];

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    public function createUser(Request $request) {
        try {

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|min:3',
                'last_name' => 'required|min:3',
                'phone' => 'required|unique:users',
                'password' => 'required|min:8',
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            $userDetails = $request->all();
            $userDetails['password'] = Hash::make($request->get('password'));
            if(User::create($userDetails)) {
                return $this->returnJsonResponse(true, 'Success');
            } else {
                return $this->returnJsonResponse(false, "Something went wrong");
            }

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    public function createAdmin(Request $request) {
        try {

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|min:3',
                'last_name' => 'required|min:3',
                'email' => 'required|unique:users',
                'password' => 'required|min:8',
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            $userDetails = $request->all();
            $userDetails['password'] = Hash::make($request->get('password'));
            $user = User::create($userDetails);
            if($user) {
                $user->assignRole('admin');
                return $this->returnJsonResponse(true, 'Success');
            } else {
                return $this->returnJsonResponse(false, "Something went wrong");
            }

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    public function fileUpload(Request $request) {

        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required',
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, $validator->errors()->first(), ["errors" => $validator->errors()->toJson()]);
            }

            $folder = '/uploads/';
            $filePath = null;

            $file = $request->file('file');
            $file_name = Str::slug($request->input('name')).'_'.time();
            $filePath = $folder . $file_name. '.' . $file->getClientOriginalExtension();
            $this->uploadOne($file, $folder, 'public', $file_name);

            return $this->returnJsonResponse(true, 'Success', ['path' => $filePath]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }
}
