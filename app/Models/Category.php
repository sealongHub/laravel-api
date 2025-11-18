<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Category extends Model
{

    protected $table = 'categories';
    protected $fillable = [
        'name',
        'description',
        'status',
        'parent_id'
    ];

    //one category hasMany product
    public function products(){
        return $this->hasMany(Product::class);
    }
}
