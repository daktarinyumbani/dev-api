<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderStatus;
use App\Models\OrderDeliveryAddress;
use App\Models\OrderItem;
use App\Models\UserDeliveryAddress;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;




class OrderController extends Controller


{
    //

    public function orders(Request $request)

    {

        $user_type=$request->user_type;
        $user_id=$request->user_id;
        $business_id=$request->business_id;
        $order_type=$request->order_type;

       

        try {
            if($order_type=='active'){
            $statuses=['New order','Accepted','Pending','Released'];
              }elseif($order_type=='new'){
                $statuses=['New order']; 
              }elseif($order_type=='past')
              {
                $statuses=['Rejected','Cancelled','Fulfilled'];
              }else{

                return $this->returnJsonResponse(true, 'Success', []);
              }
      

        if($user_type=='user'){
        $orders=Order::where('user_id',$user_id)
         ->whereIn('status',$statuses)->get();
        }elseif($user_type=='business')
        {
            $business = Business::findorfail($business_id);
            $orders=Order::where('business_id',$business_id)
            ->whereIn('status',$statuses)->get();  
        }else{
            return $this->returnJsonResponse(true, 'Success', []);
        }
       
        if ($orders->count()) {

            $items_info=[];
            $retailer_status=[];
            foreach ($orders as $order) {
                $order_status = $order->order_status;

                foreach ($order_status as $statuses) {

                    $retailer_status[] = [
                        'time' => date("g:i a", strtotime($statuses->created_at)),
                        'status' => $statuses->status,
                    ];

                }
                $items = $order->order_item;

              
                foreach ($items as $item) {

                    $items_info[] = [
                        'qty' => $item->qty,
                        'price' => $item->amount,
                        'name' => $item->product->brand->name,
                    ];

                   
                }

                $order_number=generate_report_number('ORD', $order->id, 5);

                $response[] = [
                    'id'=>$order->id,
                    'order_status'=>$order->status,
                    'order_id' =>$order_number,
                    'amount' => number_format($order->amount, 2),
                    'order_created' => date("j F", strtotime($order->created_at)),
                    'order_statuses' => $retailer_status,
                    'items' => $items_info,
                    'business'=>$order->business->name,
                    'instruction'=>$order->delivery_address->delivery_instruction,
                    'time'=>$order->delivery_address->day,
                    'phone'=>$order->user->phone,
                    'user'=>$order->user->first_name.' '.$order->user->last_name,
                ];

            }
            return $this->returnJsonResponse(true, 'Success', $response);

        } else {
            return $this->returnJsonResponse(true, 'Success', []);
        }

    } catch (\Exception $exception) {

        Log::error($exception->getMessage());
        Log::error($exception->getTraceAsString());
        return $this->returnJsonResponse(false, $exception->getMessage(), []);

       }
    }

    public function order_actions(Request $request,$order_id)

    {
        $action_type=$request->action_type;

        try {
         $order=Order::findorfail($order_id);

         if($action_type=='accept'){
               $order->update([
                'status'=>'Accepted'
               ]);
            
               $order->order_status->last()->update([
                'flag'=>'passed'
               ]);
               $order_statuses=OrderStatus::create([
                'order_id'=>$order->id,
                'status'=>'Accepted',
                'user_id'=>1
               ]);

         }elseif($action_type=='rejected')
         {
            $order->update([
                'status'=>'Rejected'
               ]);
            
               $order->order_status->last()->update([
                'flag'=>'passed'
               ]);
              $order_statuses= OrderStatus::create([
                'order_id'=>$order->id,
                'status'=>'Rejected',
                'user_id'=>1
               ]);
         }else{
            return $this->returnJsonResponse(true, 'Fail', []); 
         }


         $order_status = $order->order_status;
         $user_status=[];

         foreach ($order_status as $statuses) {

             $user_status[] = [
                 'time' => date("g:i a", strtotime($statuses->created_at)),
                 'status' => $statuses->status,
             ];

         }
         $items = $order->order_item;

       
         foreach ($items as $item) {

             $items_info[] = [
                 'qty' => $item->qty,
                 'price' => $item->amount,
                 'name' => $item->product->brand->name,
             ];

            
         }

         $order_number=generate_report_number('ORD', $order->id, 5);


         $response= [
             'id'=>$order->id,
             'order_status'=>$order->status,
             'order_id' =>$order_number,
             'amount' => number_format($order->amount, 2),
             'order_created' => date("j F", strtotime($order->created_at)),
             'order_statuses' => $user_status,
             'items' => $items_info,
             'business'=>$order->business->name,
             'user'=>$order->user->first_name.' '.$order->user->last_name,
         ];

         return $this->returnJsonResponse(true, 'Success', $response);
        
        } catch (\Exception $exception) {

            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
    
           }
    }


    public function place_order(Request $request)

    {


        try{

        $user_id=$request->user_id;
        $orders=$request->orders;
        $user=User::find($user_id);
        $grouped_orders=group_by('business_id',$orders);

        foreach ($grouped_orders as $g_orders) {
            $amount = 0;
            foreach ($g_orders as $specific_order) {
                $specific_order = (object) $specific_order;
                $amount += $specific_order->selling_price*$specific_order->quantity;

            }
            $order = Order::create([
                'amount' => $amount,
                'status' => 'New order',
                'user_id' => $user_id,
                'business_id' => $specific_order->business_id,
            ]);

            OrderStatus::create([
                'order_id' => $order->id,
                'status' => 'New order',
                'user_id' =>1,
            ]);

            foreach ($g_orders as $cart_order) {

                $cart_order = (object) $cart_order;
                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'qty' => $cart_order->quantity,
                    'amount' => $cart_order->selling_price,
                    'product_id' => $cart_order->product_id,
                ]);

            }

            $address = $request->deliveryAddress;
            $phone_number = $request->phoneNumber;
            $delivery_method = $request->deliveryMethod;
            $delivery_instruction = $request->deliveryInstruction;
            $day = $request->day;
            $time = $request->deliveryTime;

            $delivery_method == 'office' ? $delivery_method = 1 : $delivery_method = 2;

            if ($delivery_method == 1) {

                $previous_address = UserDeliveryAddress::where('user_id', $user_id)
                    ->where('status', 'active')->first();
                if (!is_null($previous_address)) {
                    $previous_address->update([
                        'status' => 'in active',
                    ]);
                }

                $user_delivery_address = UserDeliveryAddress::create([
                    'user_id' => $user_id,
                    'status' => 'active',
                    'phone' => $phone_number,
                    'address' => $address,
                ]);
            }

            $current_date = \carbon\Carbon::now();
            
            OrderDeliveryAddress::create([
                'order_id' => $order->id,
                'user_delivery_address_id' => $user_delivery_address->id,
                'delivery_method_id' => $delivery_method,
                'delivery_instructions' => $delivery_instruction,
                'day' =>$request->day
            ]);

        }


        $order_status = $order->order_status;
        $user_status=[];

        foreach ($order_status as $statuses) {

            $user_status[] = [
                'time' => date("g:i a", strtotime($statuses->created_at)),
                'status' => $statuses->status,
            ];

        }
        $items = $order->order_item;

      
        foreach ($items as $item) {

            $items_info[] = [
                'qty' => $item->qty,
                'price' => $item->amount,
                'name' => $item->product->brand->name,
            ];

           
        }

        $order_number=generate_report_number('ORD', $order->id, 5);


        $response= [
            'id'=>$order->id,
            'order_status'=>$order->status,
            'order_id' =>$order_number,
            'amount' => number_format($order->amount, 2),
            'order_created' => date("j F", strtotime($order->created_at)),
            'order_statuses' => $user_status,
            'items' => $items_info,
            'business'=>$order->business->name,
            'user'=>$order->user->first_name.' '.$order->user->last_name,
        ];

        return $this->returnJsonResponse(true, 'Success', $response);

    } catch (\Exception $exception) {

        Log::error($exception->getMessage());
        Log::error($exception->getTraceAsString());
        return $this->returnJsonResponse(false, $exception->getMessage(), []);

       }

    }
    
}
