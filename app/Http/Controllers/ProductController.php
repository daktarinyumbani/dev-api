<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\ProductImage;
use App\Models\BusinessProduct;
use App\Models\Product;
use App\Models\Brand;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class ProductController extends Controller

{
    

    public function index()

    {
        try {
            
           $products=Product::with('brand','images')->get();

        
              $generic=[];
           foreach($products as $product){
             $generic=$product->brand->generic;

             $product['generic']=$generic;
           }

           $data = [
            'products' => $products
        ];

            return $this->returnJsonResponse(true, 'Success', $data);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }
        
    }


    public function store(Request $request)

    {

        try {

        $validator = Validator::make($request->all(), [
            "name" => "required",
        ]);

        if($validator->fails()) {
            return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
        }

        $brand=Brand::create([
           'generic_id'=>$request->generic,
           'name'=>$request->name
        ]);

        $product=Product::create([
          'brand_id'=>$brand->id,
        ]);



       //  foreach($request->images as $img_url){
        $productImages=ProductImage::create([
           'product_id'=>$product->id,
           'img_url'=>$request->images
       ]);
   // }

    return $this->returnJsonResponse(true, 'Success', $product);

    } catch (\Exception $exception) {
        Log::error($exception->getMessage());
        return $this->returnJsonResponse(false, $exception->getMessage(), []);
    }


    }


    public function update(Request $request, $id)
    
    {
        try {

            $product=Product::findorfail($id);
            $brand= $product->brand; 

            $brand->update([
           'generic_id'=>$request->generic,
           'name'=>$request->name
            ]);
            $brand->product->images->each->delete();
            foreach($request->images as $img_url){
                $productImages=ProductImage::create([
                    'product_id'=>$brand->product->id,
                    'img_url'=>$img_url
                 ]); 
            }

            return $this->returnJsonResponse(true, 'Success', $product);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }

    }


    public function destroy($id)
    {
        try {

            $product=Product::findorfail($id);
            $brand= $product->brand; 
            $brand->product->images->each->delete();
            $product->delete();
            $brand->delete();

            return $this->returnJsonResponse(true, 'Success', []);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
        }

    }

    public function get_business_products($business_id)

    {   
        try {

            $business = Business::findorfail($business_id); 
            $data=BusinessProduct::Join('products','products.id','=','business_products.product_id')
                                ->join('businesses','businesses.id','=','business_products.business_id')
                                ->join('brands','brands.id','=','products.brand_id')
                                ->selectRaw('products.id as product_id, businesses.id as business_id,sum(remaining_qty) as available_qty, brands.name as product,selling_price,buying_price,businesses.name as business')
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

    public function business_create_product(Request $request, $business_id)

    {

        try {

        $validator = Validator::make($request->all(), [
            "buying_price" => "required|min:3",
            "selling_price" => "required|min:3",
            'product_id'=>"required"
        ]);

        if($validator->fails()) {
            return $this->returnJsonResponse(false, 'Validation failed.', ["errors" => $validator->errors()->toJson()]);
        }

        $business = Business::findorfail($business_id);
        
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
         'qty'=>$request->qty,
         'remaining_qty'=>$request->qty,
         'banch'=>$bunch,
         'status'=>'in stock',
         'recorded_by'=>1,
         'buying_price'=>$request->buying_price,
         'selling_price'=>$request->selling_price
        ]);

         $product=BusinessProduct::find($business_product->id)
         ->Join('products','products.id','=','business_products.product_id')
                                ->join('businesses','businesses.id','=','business_products.business_id')
                                ->join('brands','brands.id','=','products.brand_id')
                                ->selectRaw('products.id as product_id,businesses.id as business_id, sum(remaining_qty) as available_qty, brands.name as product,selling_price,buying_price,businesses.name as business')
                                ->where('business_id',$business->id)
                                ->groupBy('products.id')
                                ->get()->first();
            
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

                 return $this->returnJsonResponse(true, 'Success', $product);

             } catch (\Exception $exception) {

                    Log::error($exception->getMessage());
                    Log::error($exception->getTraceAsString());
                    return $this->returnJsonResponse(false, $exception->getMessage(), []);
          }

    }

    public function business_update_product(Request $request,$business_id,$business_product_id)

    {
             
           
        try {

            $business = Business::findorfail($business_id);
            $business_product=BusinessProduct::findorfail($business_product_id);

            $business_product->update([

                'buying_price'=>$request->buying_price,
                'selling_price'=>$request->selling_price,
                'qty'=>$request->quantity,
                'remaining_qty'=>$request->quantity,
            ]);
            
            $business_product_data=BusinessProduct::find($business_product->id)->Join('products','products.id','=','business_products.product_id')
            ->join('businesses','businesses.id','=','business_products.business_id')
            ->join('brands','brands.id','=','products.brand_id')
            ->selectRaw('products.id as product_id,businesses.id as business_id, sum(remaining_qty) as available_qty, brands.name as product,selling_price,buying_price,businesses.name as business')
            ->where('business_id',$business->id)
            ->groupBy('products.id')
            ->get()->first();

            $images=ProductImage::where('product_id',$business_product->product_id)->get();
            $image_container=[];

                 foreach($images as $image)

                 {

                  $image_container[]=[
                      'id'=>$image->id,
                      'img_url'=>$image->img_url
                    ];
                 }

                 $business_product_data['images']=$image_container;

         return $this->returnJsonResponse(true, 'Success', $business_product_data);


        } catch (\Exception $exception) {

            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
  }

    }

    public function business_delete_product($business_id,$business_product_id)

    {
        try { 

            $business = Business::findorfail($business_id);
            $business_product=BusinessProduct::findorfail($business_product_id);

            $business_product->delete();

        } catch (\Exception $exception) {

            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return $this->returnJsonResponse(false, $exception->getMessage(), []);
  }

         return $this->returnJsonResponse(true, 'Success', []);
         
    }


    public function business_most_requested_products($business_id)

    {

        try {

            $business = Business::findorfail($business_id);
       
        $business_product_data=BusinessProduct::where('business_id',$business_id)
        ->Join('products','products.id','=','business_products.product_id')
        ->join('businesses','businesses.id','=','business_products.business_id')
        ->join('brands','brands.id','=','products.brand_id')
        ->selectRaw('products.id as product_id,businesses.id as business_id,sum(remaining_qty) as available_qty, 
        (((sum(qty)-sum(remaining_qty))/sum(qty))*100) as percentage_qty,
        (sum(qty)-sum(remaining_qty)) as sum_qty_used,
        brands.name as product,selling_price,buying_price,businesses.name as business')
        ->where('business_id',$business->id)
        ->orderBy('brands.name','asc')
        ->havingRaw('sum_qty_used>=?',[0])
        ->havingRaw('percentage_qty>=?',[0])
        ->groupBy('products.id')
       ->limit(10)
        ->get();

        foreach($business_product_data as $product){

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

            return $this->returnJsonResponse(true, 'Success', $business_product_data);

            } catch (\Exception $exception) {

                Log::error($exception->getMessage());
                Log::error($exception->getTraceAsString());
                return $this->returnJsonResponse(false, $exception->getMessage(), []);
      }
    
   

    }

    public function business_out_of_stock_products($business_id)

    {
        try {

            $business = Business::findorfail($business_id);

            $business_product_data=BusinessProduct::where('business_id',$business_id)
            ->Join('products','products.id','=','business_products.product_id')
            ->join('businesses','businesses.id','=','business_products.business_id')
            ->join('brands','brands.id','=','products.brand_id')
            ->selectRaw('products.id as product_id,businesses.id as business_id, sum(remaining_qty) as available_qty, 
            brands.name as product,selling_price,buying_price,businesses.name as business')
            ->where('business_id',$business->id)
            ->orderBy('brands.name','asc')
            ->havingRaw('available_qty <=?',[0])
            ->groupBy('products.id')
           ->limit(10)
            ->get();

            foreach($business_product_data as $product){

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
        
       return $this->returnJsonResponse(true, 'Success', $business_product_data);
        
    } catch (\Exception $exception) {

        Log::error($exception->getMessage());
        Log::error($exception->getTraceAsString());
        return $this->returnJsonResponse(false, $exception->getMessage(), []);

       }
    }


   


}
