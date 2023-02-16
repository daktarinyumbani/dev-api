<?php

namespace App\Http\Controllers;

use App\Models\Ambulance;
use App\Models\AmbulanceRequest;
use App\Models\User;
use App\Traits\Users;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                "cost" => "required",
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            $user = $this->getOrCreateUser($request->all());

            $serviceProviderDetails = $request->all();
            $serviceProviderDetails['user_id'] = $user->id;
            $serviceProvider = Ambulance::create($serviceProviderDetails);
            if($serviceProvider) {
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

            $ambulance = Ambulance::with(['user', 'reviews', 'requests' => function($q) use($liveStatuses) {
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
     * @param  int $id
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

            $serviceProvider = Ambulance::with('user')->findOrFail($id);

            if($serviceProvider->update($request->all())) {

                $userDetails = null;
                if($request->has('first_name')) {
                    $userDetails['first_name'] = $request->get('first_name');
                }
                if($request->has('last_name')) {
                    $userDetails['last_name'] = $request->get('last_name');
                }
                if($request->has('picture')) {
                    $userDetails['picture'] = $request->get('picture');
                }

                if($userDetails != null) {
                    //update the user
                    $serviceProvider->user()->update($userDetails);
                }

                $serviceProvider->refresh();

                return $this->returnJsonResponse(true, 'Success', ['ambulance' => $serviceProvider]);
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
                ->where('type', '=', $ambulanceType)
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

    /**
     * Get upcoming requests pending
     */
    public function getUpcomingRequests() {
        try {

            $user = User::with('ambulance')->find(Auth::id());

            $requests = AmbulanceRequest::with('user', 'ambulance.user')
                ->where('ambulance_id', '=', $user->ambulance->id)
                ->whereIn('status', ['NEW', 'ACCEPTED'])
                ->orderBy("date", 'asc')
                ->limit(2)
                ->get();

            $data = [
                'requests' => $requests
            ];

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    /**
     * Get live requests
     */
    public function getLiveRequest() {
        try {

            $user = User::with('ambulance')->find(Auth::id());

            $liveStatuses = ['ACCEPTED', 'STARTED', 'PENDING'];

            $request = AmbulanceRequest::with('user', 'ambulance.user')
                ->where('ambulance_id', '=', $user->ambulance->id)
                ->whereIn('status', $liveStatuses)
                ->orderBy("date", 'asc')
                ->first();

            $data = [
                'request' => $request
            ];

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    /**
     * Get Requests for this ambulance
     * @param Request $request
     * @return JsonResponse
     */
    public function getRequests(Request $request) {
        try {

            $user = User::with('ambulance')->find(Auth::id());

            $requestQuery = AmbulanceRequest::with('user', 'ambulance.user')
                ->where('ambulance_id', '=', $user->ambulance->id);


            if($request->has('dateFrom') && $request->has('dateTo')) {

                $dateTo = $request->get('dateTo') . ' 23:59:59';

                $requestQuery->whereBetween('created_at', [
                        $request->get('dateFrom'), $dateTo]
                );
            }

            $requestQuery->orderBy("created_at", 'asc');
            $requests = $requestQuery->get();

            $data = [
                'requests' => $requests
            ];

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }
}
