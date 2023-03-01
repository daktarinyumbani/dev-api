<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable=[
        'status',
        'business_id',
        'user_id',
        'amount'

    ];

    public function order_status()

    {
        return $this->hasMany(OrderStatus::class);
    }

    public function order_item()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class); 
    }

    public function user()
    {
        return $this->belongsTo(User::class); 
    }

    public function delivery_address()

    {
        return $this->hasOne(OrderDeliveryAddress::class);
    }

    public function user_delivery_address()
    {
        return $this->hasMany(UserDeliveryAddress::class);
    }
}
