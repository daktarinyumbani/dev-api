<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Traits\Users;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServiceProviderController extends Controller
{
    use Users;
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        //TODO add pagination
        //TODO add filters to this method for:
        //service
        //specialty

        try {

            $providers = ServiceProvider::with('services', 'user', 'specialty.category')->get();

            $data = [
                'providers' => $providers
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
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "first_name" => "required|min:3",
                "last_name" => "required|min:3",
                "phone" => "required",
                "password" => "required|min:8",
                "specialty_id" => "required",
                "qualification" => "required",
                "reg_number" => "required",
                "board_status" => "required",
                "current_hospital" => "required",
                "bio" => "required",
                "cost" => "required",
                "services" => "required|array"
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            $user = $this->getOrCreateUser($request->all());

            $serviceProviderDetails = $request->all();
            $serviceProviderDetails['user_id'] = $user->id;
            $serviceProvider = ServiceProvider::create($serviceProviderDetails);
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
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {

            $liveStatuses = ['NEW', 'ACCEPTED', 'STARTED', 'PENDING'];

            $serviceProvider = ServiceProvider::with(['user', 'specialty', 'services','requests.service', 'requests' => function($q) use($liveStatuses) {
                $q->whereIn('service_requests.status', $liveStatuses);
            }])
            ->findOrFail($id);

            $data = [
                'provider' => $serviceProvider
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
     * @param Service $service
     * @return Response
     */
    public function edit(Service $service)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {

            $serviceProvider = ServiceProvider::findOrFail($id);

            if($serviceProvider->update($request->all())) {

                if($request->has('services')) {
                    //sync the services provided
                    $serviceProvider->sync($request->get('services'));
                }
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
     * @param ServiceProvider $service
     * @return Response
     */
    public function destroy(ServiceProvider $service)
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
                "service" => "required",
                "specialty" => "required",
            ]);
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');

            if ($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            /*
             * replace 6371000 with 6371 for kilometer and 3956 for miles
             */
            $serviceProviders = ServiceProvider::with('user', 'specialty', 'services')
                ->selectRaw("id, user_id, specialty_id, company_name, address, latitude, longitude, cost, bio,
                         ( 6371 * acos( cos( radians(?) ) *
                           cos( radians( latitude ) )
                           * cos( radians( longitude ) - radians(?)
                           ) + sin( radians(?) ) *
                           sin( radians( latitude ) ) )
                         ) AS distance", [$latitude, $longitude, $latitude])
//            ->where('active', '=', 1)
                ->having("distance", "<", $radius)
                ->orderBy("distance", 'asc')
                ->offset(0)
                ->limit(20)
                ->get();

            return $this->returnJsonResponse(true, 'Success', ["providers" => $serviceProviders]);
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

            $user = User::with('serviceProvider')->find(Auth::id());

            $requests = ServiceRequest::with('user', 'serviceProvider.user', 'serviceProvider.specialty', 'service')
                ->where('service_provider_id', '=', $user->serviceProvider->id)
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

            $user = User::with('serviceProvider')->find(Auth::id());

            $liveStatuses = ['ACCEPTED', 'STARTED', 'PENDING'];

            $request = ServiceRequest::with('user', 'serviceProvider.user', 'serviceProvider.specialty', 'service')
                ->where('service_provider_id', '=', $user->serviceProvider->id)
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
     * Get Requests for this service provider
     */
    public function getRequests() {
        try {

            $user = User::with('serviceProvider')->find(Auth::id());

            $request = ServiceRequest::with('user', 'serviceProvider.user', 'serviceProvider.specialty', 'service')
                ->where('service_provider_id', '=', $user->serviceProvider->id)
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

//    /**
//     * Remove the specified resource from storage.
//     *
//     * @param Request $request
//     * @return Response
//     */
//    public function search(Request $request)
//    {
//        $radius = 400;
//
//        //service
//        //specialty
//
//        /*
//         * replace 6371000 with 6371 for kilometer and 3956 for miles
//         */
//        $restaurants = ServiceProvider::selectRaw("id, name, address, latitude, longitude, rating, zone ,
//                         ( 6371000 * acos( cos( radians(?) ) *
//                           cos( radians( latitude ) )
//                           * cos( radians( longitude ) - radians(?)
//                           ) + sin( radians(?) ) *
//                           sin( radians( latitude ) ) )
//                         ) AS distance", [$latitude, $longitude, $latitude])
//            ->where('active', '=', 1)
//            ->having("distance", "<", $radius)
//            ->orderBy("distance",'asc')
//            ->offset(0)
//            ->limit(20)
//            ->get();
//
//        return $restaurants;
//
//        //perform the search
//    }
}
