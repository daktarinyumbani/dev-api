<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SpecialtyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        //
        try {

            $specialties = Specialty::with('category')->get();

            $data = [
                'specialties' => $specialties
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
        //
        try {

            $validator = Validator::make($request->all(), [
                "category_id" => "required",
                "name" => "required",
                "icon" => "required",
            ]);

            if($validator->fails()) {
                return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
            }

            if(Specialty::create($request->all())) {
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
     * Display the specified resource.
     *
     * @param Specialty $specialty
     * @return Response
     */
    public function show(Specialty $specialty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Specialty $specialty
     * @return Response
     */
    public function edit(Specialty $specialty)
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

            $specialty = Specialty::findOrFail($id);

            if($specialty->update($request->all())) {
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
     * @param Specialty $specialty
     * @return Response
     */
    public function destroy(Specialty $specialty)
    {
        //
    }
}
