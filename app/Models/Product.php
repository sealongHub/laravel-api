<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'quantity',
        'category_id',
        'supplier_id',
        'brand_id',
        'status',
        'image',
    ];

    // many products belong to one category
    public function category(){
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    // many products belong to one brand
    public function brand(){
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'product_id');
    }


    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function supplier()
    {
        // Ensure the foreign key 'supplier_id' matches your migration
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

}
