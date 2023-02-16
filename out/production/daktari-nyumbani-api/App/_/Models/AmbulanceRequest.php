<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmbulanceRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'ambulance_id',
        'status',
        'complete',
        'payment_status',
        'date',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function ambulance() {
        return $this->belongsTo(Ambulance::class);
    }

}
