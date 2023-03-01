<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDeliveryAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_delivery_address_id',
        'delivery_method_id',
        'delivery_instruction',
        'day'
    ];
}
