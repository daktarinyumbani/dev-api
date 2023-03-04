<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use \App\Http\Controllers\AuthController;
use \App\Http\Controllers\CategoryController;
use \App\Http\Controllers\ServiceController;
use \App\Http\Controllers\UserController;
use \App\Http\Controllers\ServiceProviderController;
use \App\Http\Controllers\ServiceRequestController;
use \App\Http\Controllers\SpecialtyController;
use \App\Http\Controllers\AmbulanceController;
use \App\Http\Controllers\AmbulanceRequestController;
use \App\Http\Controllers\BusinessController;
use \App\Http\Controllers\ReportsController;
use \App\Http\Controllers\WholesalerController;
use \App\Http\Controllers\RetailerController;
use \App\Http\Controllers\ProductController;
use \App\Http\Controllers\OrderController;
use \App\Http\Controllers\GenericController;
use \App\Http\Controllers\BrandController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['prefix' => 'v1'], function () {

    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'loginByPhone']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('verify-phone', [AuthController::class, 'verifyPhone']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    //ADMIN Routes
    Route::group(['prefix' => 'admin'], function () {
        //Auth
        Route::group(['prefix' => 'auth'], function () {
            Route::post('login', [AuthController::class, 'loginByEmail']);
            Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
            Route::post('reset-password', [AuthController::class, 'resetPassword']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::group(['prefix' => 'user'], function () {
            Route::post('set-device-token', [AuthController::class, 'setDeviceToken']);
            Route::post('update-service-provider', [AuthController::class, 'updateServiceProviderProfile']);
            Route::post('update-profile', [AuthController::class, 'updateProfile']);
        });

        Route::group(['prefix' => 'products'], function () {
        
        Route::get('shop_products/{business_id}',[ProductController::class,'get_business_products']);
        Route::post('shop_products/{business_id}',[ProductController::class,'business_create_product']);
        Route::post('shop_products/{business_id}/{business_product_id}',[ProductController::class,'business_update_product']);
        Route::delete('shop_products/{business_id}/{business_product_id}',[ProductController::class,'business_delete_product']);
        Route::get('business_most_requested_products/{business_id}',[ProductController::class,'business_most_requested_products']);
        Route::get('business_out_of_stock_products/{business_id}',[ProductController::class,'business_out_of_stock_products']);
        });
    
       
        Route::group(['prefix' => 'orders'], function () {
            Route::get('/',[OrderController::class,'orders']);
            Route::post('/order_actions/{order_id}',[OrderController::class,'order_actions']); 
            Route::post('/place_order/{user_id}',[OrderController::class,'place_order']); 

        });

        Route::post('file-upload', [UserController::class, 'fileUpload']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('specialties',[SpecialtyController::class, 'index']);
        Route::get('service-providers/search/{name}', [ServiceProviderController::class, 'searchByName']);
        Route::post('service-providers/search-nearby', [ServiceProviderController::class, 'searchNearby']);

        Route::get('service-providers/upcoming-requests', [ServiceProviderController::class, 'getUpcomingRequests']);
        Route::get('service-providers/live-request', [ServiceProviderController::class, 'getLiveRequest']);
        Route::get('service-providers/requests', [ServiceProviderController::class, 'getRequests']);
        Route::resource('service-providers', ServiceProviderController::class);

        Route::get('service-requests/live', [ServiceRequestController::class, 'getLiveRequest']);
        Route::post('service-requests/refer/{id}', [ServiceRequestController::class, 'refer']);
        Route::post('service-requests/review/{id}', [ServiceRequestController::class, 'postReview']);
//        Route::get('service-requests/upcoming', [ServiceRequestController::class, 'getUpcomingRequest']);
        Route::resource('service-requests', ServiceRequestController::class);

        Route::get('ambulances/upcoming-requests', [AmbulanceController::class, 'getUpcomingRequests']);
        Route::get('ambulances/live-request', [AmbulanceController::class, 'getLiveRequest']);
        Route::get('ambulances/requests', [AmbulanceController::class, 'getRequests']);
        Route::post('ambulances/search-nearby', [AmbulanceController::class, 'searchNearby']);
        Route::resource('ambulances', AmbulanceController::class);


        Route::resource('ambulance-requests', AmbulanceRequestController::class);
        Route::post('ambulance-requests/review/{id}', [AmbulanceRequestController::class, 'postReview']);

        
        Route::post('businesses/search-nearby',[BusinessController::class,'getBusinessType']);
        Route::get('businesses/{business_type}',[BusinessController::class,'get_business_type']);
        Route::resource('businesses', BusinessController::class);

        // Route::resource('wholesalers', WholesalerController::class);
        // Route::resource('retailers', RetailerController::class);


        //ADMIN Routes
        Route::group(['prefix' => 'admin'], function () {
            //Auth
//            Route::group(['prefix' => 'auth'], function () {
//                Route::post('login', [AuthController::class, 'loginByEmail']);
//                Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
//                Route::post('reset-password', [AuthController::class, 'resetPassword']);
//            });

            Route::post('reports/summary', [ReportsController::class, 'summary']);

            //TODO add admin middleware from here
            Route::group(['prefix' => 'users'], function () {
                Route::get('all', [UserController::class, 'index']);
                Route::post('create', [UserController::class, 'createUser']);
                Route::post('create-admin', [UserController::class, 'createAdmin']);
            });

            //Resource control
            Route::resource('categories', CategoryController::class);
            Route::resource('specialties',SpecialtyController::class);
            Route::resource('services', ServiceController::class);
            Route::resource('service-providers', ServiceProviderController::class);
            Route::resource('ambulances', AmbulanceController::class);
            Route::resource('businesses', BusinessController::class);
            Route::get('service-requests/all', [ServiceRequestController::class, 'all']);
            Route::resource('service-requests', ServiceRequestController::class);
            Route::resource('generics', GenericController::class);
            Route::resource('brands', BrandController::class);
            Route::get('products',[ProductController::class,'index']);
            Route::post('products',[ProductController::class,'store']);
            Route::get('products/{id}',[ProductController::class,'show']);
            Route::post('products/{id}',[ProductController::class,'update']);

        });
    });
});
