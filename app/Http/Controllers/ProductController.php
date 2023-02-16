<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\ProductImage;
use App\Models\BusinessProduct;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;


class ProductController extends Controller

{
    //

    public function get_business_products($id)

    {

        try {

            $business = Business::findorfail($id);
             
            $data=BusinessProduct::Join('products','products.id','=','business_products.product_id')
                                ->join('businesses','businesses.id','=','business_products.business_id')
                                ->join('brands','brands.id','=','products.brand_id')
                                ->selectRaw('products.id as product_id,sum(remaining_qty) as available_qty, brands.name as product,selling_price as price,businesses.name as business')
                                ->where('business_id',$business->id)
                                ->groupBy('products.id')
                                ->get();
 
                   foreach($data as $product){
    
                  $images=ProductImage::where('product_id',$product->product_id)->get();
                  $image_container=[];

                       foreach($images as $image)

                       {
                        $image_container[]=[
                            'id'=>$image->id,
                            'img_url'=>$image->img_url
                          ];
                       }

                    $product['images']=$image_container;
                    
                   }

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
    }

    public function business_create_product($id)

    {
        $business = Business::findorfail($id);
        
        //business product
        $exist_product=BusinessProduct::where('product_id',$request->product_id)
        ->where('business_id',$business->id)
        ->latest()->first();

         if($exist_product){
            $bunch=$exist_product->banch+1;
         }else{
            $bunch=1;
         }
        $business_product=BusinessProduct::create([

         'business_id'=>$business->id,
         'product_id'=>$request->product_id,
         'qty'=>$request->quantity,
         'remaining_qty'=>$request->quantity,
         'banch'=>$bunch,
         'status'=>'in stock',
         'recorded_by'=>1,
         'buying_price'=>$request->buying_price,
         'selling_price'=>$request->selling_price
        ]);

        //return single product data.

    }
}
