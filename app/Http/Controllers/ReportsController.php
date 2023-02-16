<?php

namespace App\Http\Controllers;

use App\Models\Ambulance;
use App\Models\AmbulanceRequest;
use App\Models\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportsController extends Controller
{
    //
    public function summary(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'from' => 'required',
                'to' => 'required',
            ]);

            $unit = "day";
            if($request->has('unit')) {
                $unit = $request->get('unit');
            }

            if($validator->fails()) {
                return $this->returnJsonResponse(false, $validator->errors()->first(), ["errors" => $validator->errors()->toJson()]);
            }

            $from = $request->get('from');
            $to = $request->get('to');

            $data = [
                'summary' => array(
                    'serviceProvidersCount' => $this->getServiceProvidersCount(),
                    'usersCount' => $this->getAllUsersCount(),
                    'requestsCount' => $this->getServiceRequestCount($from, $to),
                    'ambulancesCount' => $this->getAmbulancesCount(),
                    'serviceRequestsTrend' => $this->getServiceRequestsTrend(ServiceRequest::class, $from, $to, $unit),
                    'ambulanceRequestsTrend' => $this->getServiceRequestsTrend(AmbulanceRequest::class, $from, $to, $unit)
                )
            ];

            return $this->returnJsonResponse(true, "Success", $data);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    private function getServiceProvidersCount() {
        return ServiceProvider::count();
    }

    private function getAllUsersCount() {
        return User::count();
    }

    private function getServiceRequestCount($from, $to) {
        $serviceRequestsCount = ServiceRequest::whereBetween('created_at', [$from, $to])->count();
        $ambulanceRequestsCount = AmbulanceRequest::whereBetween('created_at', [$from, $to])->count();
        return $serviceRequestsCount + $ambulanceRequestsCount;
    }

    private function getAmbulancesCount() {
        return Ambulance::count();
    }

    /**
     * @param $model
     * @param $from
     * @param $to
     * @param string $mode //allowed modes: day | hour
     * @return array
     */
    private function getServiceRequestsTrend($model, $from, $to, $mode = "day") {
        //set the carbon format based on the mode
        $format = 'd/m/Y';
        if($mode == "hour") {
            $format ='d/m/Y H';
        }

        $requestsCount = null;
        $requestsCount = $model::whereBetween('created_at', [$from, $to])
            ->select('id', 'created_at')
            ->get()
            ->groupBy(function($val) use ($format) {
                return Carbon::parse($val->created_at)->format($format);
            });

        if(!$requestsCount) { return []; }

        $dateRange = $this->generateDateRange($from, $to, $format, $mode);

        $newData = [];
        foreach ($dateRange as $date) {
            $date = Carbon::createFromFormat($format, $date);
            $dateString = $date->format($format);

            if(!isset($requestsCount[ $dateString ])) {
                $requestsCount[ $dateString ] = 0;
                $count = 0;
            } else {
                $count = count($requestsCount[ $dateString ]);
                $requestsCount[ $dateString ] = count($requestsCount[ $dateString ]);
            }
            $newData[] = array("date" => $dateString, "count" => $count);
        }

        return $newData;
    }

    private function generateDateRange($start, $end, $format, $range = "day")
    {
        try {
            $start_date = new Carbon($start);
            $end_date = new Carbon($end);

            $dates = [];

            if($range == "hour") {
                for($date = $start_date->copy(); $date->lte($end_date); $date->addHour()) {
                    $dates[] = $date->format($format);
                }
            } else {//day is default
                for($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
                    $dates[] = $date->format($format);
                }
            }

            return $dates;
        } catch (\Exception $e) {
            return [];
        }
    }
}
