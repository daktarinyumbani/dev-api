<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_id',
        'name',
        'icon'
    ];

    public function category() {
        return $this->belongsTo('App\Models\Category');
    }

    public function serviceProviders() {
        return $this->hasMany('App\Models\ServiceProvider');
    }
}
