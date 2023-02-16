<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ambulance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'reg_number',
        'board_status',
        'current_hospital',
        'bio',
        'available',
        'address',
        'longitude',
        'latitude',
        'cost',
        'active',
        'type'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function requests() {
        return $this->hasMany(AmbulanceRequest::class);
    }

    public function reviews() {
        return $this->hasManyThrough(Review::class, AmbulanceRequest::class, null, 'reviewable_id')
            ->where('reviewable_type', Ambulance::class);
    }
}
