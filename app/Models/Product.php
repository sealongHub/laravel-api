<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\Brand;

class Product extends Model
{
    protected $table = "products";
    protected $fillable = [
        "category_id",
        "brand_id",
        "product_name",
        "description",
        "quantity",
        "price",
        "status",
        "image"
    ];

    //many products to one category
    public function category() {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    //many products to one brand
    public function brand(){
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }
}
