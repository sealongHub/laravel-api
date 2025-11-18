<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $rq)
    {
        $query = Product::query();

        if($rq->has("category_id")){
            $query->where('category_id', "=", $rq->input('category_id'));
        }

        if($rq->has("brand_id")){
            $query->where('brand_id', "=", $rq->input('brand_id'));
        }

        if($rq->has("text_search")){
            $query->where('product_name', "LIKE", "%".$rq->input('text_search')."%");
        }

        if($rq->has("status")){
            $query->where('status', "=", $rq->input('status'));
        }

        $pro = $query->with(['category', 'brand'])->get(); //include relationship

        return response()->json([
            // "products" => Product::with(['category', 'brand'])->get(),
            "products" => $pro,
            "category" => Category::all(),
            "brand" => Brand::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //form data file or image 
        $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'brand_id'     => 'required|exists:brands,id',
            'product_name' => 'required|string|max:50',
            'description' => 'required|string|max:100',
            'quantity' => 'required|integer',
            'price' => 'required|numeric',
            'status' => 'boolean',
            'image' => 'required|image|mimes:jpeg,jpg,png,gif|max:2048',
        ]);
        
        $data = $request->all();

        if($request->has('image')){
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $pro = Product::create($data);

        return response()->json([
            "message" => "Create Products Successfully",
            "products" => $pro,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $pro = Product::find($id);
        $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'brand_id'     => 'required|exists:brands,id',
            'product_name' => 'required|string|max:50',
            'description' => 'required|string|max:100',
            'quantity' => 'required|integer',
            'price' => 'required|numeric',
            'status' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
        ]);

        $data = $request->all();

        if($request->has("image")){
            if($pro->image){
                Storage::disk("public")->delete($pro->image);
            }
            $data['image'] = $request->file("image")->store('products', 'public');
            
        }

        $pro->update($data);

        return response([
            "message" => "update Successfully...",
            "products" => $pro,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pro = Product::find($id);

        if(!$pro){
            return response()->json([
                "message" => "This ID not Found in the system!..",
            ]);
        }
        
        if($pro->image){
            Storage::disk("public")->delete($pro->image);
        }
        $pro->delete();

        return response()->json([
            "message" => "Delete Successfully....",
            "products" => $pro,
        ]);
    }
}
