<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Brand extends Model
{
    protected $table = "brands";
    protected $fillable = [
        'name',
        'code',
        'from_country',
        'image',
        'status',
    ];

    //one Brand hasMany Products
    public function products(){
        return $this->hasMany(Product::class);
    }
}
