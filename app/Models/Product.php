<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable=[
        'brand_id'
    ];

    public function images()

    {
        return $this->hasMany(ProductImage::class);
    }

     public function brand()
     
     {
        return $this->belongsTo(Brand::class);
     }

   
     
    
}
