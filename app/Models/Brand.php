<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;


    protected $fillable = [
        'generic_id',
        'name'
    ];

    public function generic()

    {
        return $this->belongsTo(Generic::class);
    }

   public function product()
   
   {
    return $this->hasOne(Product::class);
   }
}
