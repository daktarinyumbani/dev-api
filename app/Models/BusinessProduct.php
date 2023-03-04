<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessProduct extends Model
{
    use HasFactory;

    protected $fillable= [
        'business_id',
        'product_id',
        'qty',
        'remaining_qty',
        'banch',
        'status',
        'recorded_by',
        'buying_price',
        'selling_price',
    ];


    public function business()
    
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    
    {
        return $this->belongsTo(Product::class);
    }
}


