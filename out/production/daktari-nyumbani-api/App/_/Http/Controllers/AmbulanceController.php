<?php

namespace App\Http\Controllers;

use App\Models\Ambulance;
use App\Traits\Users;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AmbulanceController extends Controller
{
    use Users;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {

            $ambulances = Ambulance::with( 'user')->get();

            $data = [
                'ambulances' => $ambulances
            ];

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "first_name" => "required|min:3",
                "last_name" => "required|min:3",
                "phone" => "required",
                "password" => "required|min:8",
                "company_name" => "required|min:3",
                "reg_number" => "required",
                "board_status" => "required",
                "current_hospital" => "required",
                "bio" => "required",
                "location" => "required",
                "cost" => "required",
                "services" => "required|array"
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            $user = $this->getOrCreateUser($request->all());

            $serviceProviderDetails = $request->all();
            $serviceProviderDetails['user_id'] = $user->id;
            $serviceProvider = Ambulance::create($serviceProviderDetails);
            if($serviceProvider) {
                $serviceProvider->services()->sync($request->get('services'));
                return $this->returnJsonResponse(true, 'Success', []);
            } else {
                return $this->returnJsonResponse(false, "Something went wrong", []);
            }

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {

            $liveStatuses = ['NEW', 'ACCEPTED', 'STARTED', 'PENDING'];

            $ambulance = Ambulance::with(['user', 'requests' => function($q) use($liveStatuses) {
                $q->whereIn('ambulance_requests.status', $liveStatuses);
            }])->findOrFail($id);

            $data = [
                'ambulance' => $ambulance
            ];

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {

            $serviceProvider = Ambulance::findOrFail($id);

            if($serviceProvider->update($request->all())) {
                return $this->returnJsonResponse(true, 'Success', []);
            } else {
                return $this->returnJsonResponse(false, "Something went wrong", []);
            }

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Search for resources nearby
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchNearby(Request $request)
    {
        try {
            //TODO configure the radius
            $radius = 10000;
            $validator = Validator::make($request->all(), [
                "latitude" => "required",
                "longitude" => "required",
                "type" => "required",
            ]);
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            $ambulanceType = $request->get('type');

            if ($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            /*
             * replace 6371000 with 6371 for kilometer and 3956 for miles
             */
            $serviceProviders = Ambulance::with('user')
                ->selectRaw("id, user_id, company_name, address, latitude, longitude, cost, bio,
                         ( 6371 * acos( cos( radians(?) ) *
                           cos( radians( latitude ) )
                           * cos( radians( longitude ) - radians(?)
                           ) + sin( radians(?) ) *
                           sin( radians( latitude ) ) )
                         ) AS distance", [$latitude, $longitude, $latitude])
//                ->where('type', '=', $ambulanceType)
                ->having("distance", "<", $radius)
                ->orderBy("distance", 'asc')
                ->offset(0)
                ->limit(20)
                ->get();

            return $this->returnJsonResponse(true, 'Success', ["ambulances" => $serviceProviders]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }
}
