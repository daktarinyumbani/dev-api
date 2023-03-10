<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'phone',
        'address',
        'longitude',
        'latitude',
        'bio',
        'active',
        'user_id',
        'business_type',
        'doc_url',
        'doc_type'
    ];
}
