<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'specialty_id',
        'company_name',
        'qualification',
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
    ];

    protected $casts = [
        'location' => 'array'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function services() {
        return $this->belongsToMany(Service::class, 'service_providers_services');
    }

    public function specialty() {
        return $this->belongsTo(Specialty::class);
    }

    public function requests() {
        return $this->hasMany(ServiceRequest::class);
    }
}
