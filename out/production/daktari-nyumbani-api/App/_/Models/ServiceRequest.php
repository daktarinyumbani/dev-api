<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'service_provider_id',
        'service_id',
        'status',
        'complete',
        'payment_status',
        'date',
    ];

    public function user() {
        return $this->belongsTo('App\Models\User');
    }

    public function serviceProvider() {
        return $this->belongsTo('App\Models\ServiceProvider');
    }

    public function service() {
        return $this->belongsTo('App\Models\Service');
    }
}
