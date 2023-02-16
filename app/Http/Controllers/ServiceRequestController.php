<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\NewRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServiceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function all()
    {
        //TODO add pagination
        //TODO add filters to this method for:
        //service
        //specialty

        try {

            $requests = ServiceRequest::with('user', 'serviceProvider.user', 'serviceProvider.specialty', 'service', 'review')->get();

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

            $requests = ServiceRequest::with('user', 'serviceProvider.user', 'serviceProvider.specialty', 'service')->where('user_id', Auth::id())->get();

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
                "service_provider_id" => "required",
                "service_id" => "required",
                "date" => "required",
            ]);

            $requestDetails = $request->all();
            $requestDetails['date'] = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            $requestDetails['user_id'] = Auth::id();

            $requestingUser = User::find(Auth::id());

            $serviceRequest = ServiceRequest::create($requestDetails);
            if($serviceRequest) {
                //TODO notify the service provider via:
                //device token on firebase
                $serviceProvider = ServiceProvider::with('user')->findOrFail($request->get('service_provider_id'));
                $serviceProvider->user->notify(new NewRequest($requestingUser));
                return $this->returnJsonResponse(true, 'Success', [
                    'request' => $serviceRequest
                ]);
            } else {
                return $this->returnJsonResponse(false, "Something went wrong", []);
            }

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
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
            $request = ServiceRequest::with('user', 'serviceProvider.user', 'serviceProvider.specialty', 'service', 'review')->findOrFail($id);

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
        /**
         * REQUEST STATUSES:
         * - NEW (just requested by patient)
         * - ACCEPTED (request accepted by service provider)
         * - STARTED (consultation/service provision has started)
         * - PENDING (have this here in case there is any delays or something pending)
         * - COMPLETE
         * - CANCELLED
         * - DECLINED
         */
        try {

            $serviceRequest = ServiceRequest::findOrFail($id);

            $requestDetails = $request->all();

            if($request->has('date')) {
                $requestDetails['date'] = Carbon::parse($request->get('date'))->format('Y-m-d H:i:s');
            }
            if($serviceRequest->update($requestDetails)) {
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
     * @param ServiceRequest $serviceRequest
     * @return Response
     */
    public function destroy(ServiceRequest $serviceRequest)
    {
        //
    }

    /**
     * Get live requests
     */
    public function getLiveRequest() {
        try {

            $liveStatuses = ['NEW', 'ACCEPTED', 'STARTED', 'PENDING'];

            $request = ServiceRequest::with('user', 'serviceProvider.user', 'serviceProvider.specialty', 'service')
                ->where('user_id', '=', Auth::id())
                ->whereIn('status', $liveStatuses)
                ->orderBy("date", 'desc')
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
     * Get upcoming requests pending
     */
    public function getUpcomingRequest() {
        try {

            $requests = ServiceRequest::with('user', 'serviceProvider.user', 'serviceProvider.specialty', 'service')
                ->where('user_id', '=', Auth::id())
                ->orderBy("date", 'asc')
                ->limit(1)
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
     * Create review for this service request
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function postReview(Request $request, $id) {
        try {
            $validator = Validator::make($request->all(), [
                "rating" => "required",
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            $serviceRequest = ServiceRequest::findOrFail($id);
            $review = new Review($request->all());

            if($serviceRequest->review()->save($review)) {
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
     * Refer to another service provider
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function refer(Request $request, $id) {
        try {
            $validator = Validator::make($request->all(), [
                "service_provider_id" => "required",
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            $user = User::with('serviceProvider', 'ambulance')->findOrFail($request->get('service_provider_id'));

            if(!$user->serviceProvider()->exists()) {
                return $this->returnJsonResponse(false, 'No service provider found.', []);
            }

            $serviceRequest = ServiceRequest::findOrFail($id);
            $serviceRequest->service_provider_id = $user->serviceProvider->id;

            if($serviceRequest->save()) {
                $user->notify(new NewRequest());
                return $this->returnJsonResponse(true, 'Success', []);
            } else {
                return $this->returnJsonResponse(false, "Something went wrong", []);
            }

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }
}
