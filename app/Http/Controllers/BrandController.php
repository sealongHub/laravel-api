<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $rq)
    {
        $query = Brand::query();

        if($rq->has("text_search")){
            $query->where('name', "LIKE", "%".$rq->input("text_search")."%");
        }

        if($rq->has("status")){
            $query->where('status', "=", $rq->input("status"));
        }

        $brand = $query->get();

        return response()->json([
            "brands" => Brand::all(),
            "brands" => $brand,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|unique:brands,code,',
            'from_country' => 'required|string|max:100',
            'status' => 'required|in:active,in active',
            // 'status' => 'required|in:active,in active',

            'image' => 'required|image|mimes:jpeg,jpg,png,gif|max:2048',
        ]);

        $data = $request->all();
        if($request->has("image")){
            $data['image'] = $request->file("image")->store("brands", "public");
        }

        // $brand = Brand::create([
        //     'name' => $request->name,
        //     'code' => $request->code,
        //     'from_country' => $request->from_country,
        //     'status' => $request->status,
        //     "image" => $imagePath,
        // ]);
        $brand = Brand::create($data);

        return response()->json([
            "message" => "Insert Brand Successfully",
            "brands " => $brand,
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $brand = Brand::find($id);

        return response()->json([
            "message" => "Get Data Specified",
            "brands" => $brand
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|unique:brands,code,' . $id . ',id',
            'from_country' => 'required|string|max:255',
            'status' => 'required|in:active,in active',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_remove' => "nullable|string",
        ]);

        //Handle the image upload

        $brand = Brand::find($id);

        if($request->has("image")){
            
            //delete image
            if($brand->image){
                Storage::disk("public")->delete($brand->image);
            }

            //store image
            $imagePath = $request->file("image")->store("brands", "public");
            $brand->image = $imagePath;
        }
        if($request->image_remove !=""){
            Storage::disk("public")->delete($request->image_remove);
            $brand->image = null;
        }

        $brand->update([
            'name' => $request->name,
            'code' => $request->code,
            'from_country' => $request->from_country,
            'status' => $request->status,
            // "image" => $imagePath,
        ]);

        return response()->json([
            "message" => "Update Brand Successfully",
            "brands " => $brand,
        ]);
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        $brand = Brand::find($id);

        if($brand->image){
            Storage::disk("public")->delete($brand->image);
        }

        $brand->delete();

        return response()->json([
            "message" => "Delete Successfully",
            "brands" => $brand,
        ],200);
    }
}