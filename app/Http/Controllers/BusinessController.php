<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {

        try {

            $businesses = Business::all();

            $data = [
                'businesses' => $businesses
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
                "name" => "required|min:3",
                "type" => "in:pharmacy,insurance",
                "phone" => "required",
                "address" => "required",
                "latitude" => "required",
                "longitude" => "required",
                "bio" => "required",
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            if(Business::create($request->all())) {
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

            $business = Business::findOrFail($id);

            $data = [
                'business' => $business
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

            $business = Business::findOrFail($id);

            if($business->update($request->all())) {
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


    public function getBusinessType(Request $request){

         $radius = 10000;
         $validator = Validator::make($request->all(), [
             "latitude" => "required",
             "longitude" => "required",
         ]);
         $latitude = $request->get('latitude');
         $longitude = $request->get('longitude');
         $businessType = $request->get('business_type');
      
         try {
            $businesses = Business::selectRaw("id, user_id,type,name, address, latitude, longitude,doc_url,active, bio,
            ( 6371 * acos( cos( radians(?) ) *
              cos( radians( latitude ) )
              * cos( radians( longitude ) - radians(?)
              ) + sin( radians(?) ) *
              sin( radians( latitude ) ) )
            ) AS distance", [$latitude, $longitude, $latitude])
   ->where('business_type', '=', $businessType)
   ->having("distance", "<", $radius)
   ->orderBy("distance", 'asc')
   ->offset(0)
   ->limit(20)
   ->get();
            $data = [
                'business_type'=> $businessType,
                'businesses' =>$businesses
                
            ];

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {

            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }

    }

    public function get_business_type($business_type)

    {

        try {


            $businesses = Business::where('business_type',$business_type)->get();

            $data = [
                'businesses' =>$businesses
            ];

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {

            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }


}
