<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $rq)
    {
        $cat = Category::query();
        if($rq->has('text_search')){
            $cat->where('name', 'LIKE',  "%".$rq->input('text_search').'%');
        }
        if($rq->has('status')){
            $cat->where("status", "=", $rq->input("status"));
        }

        $list = $cat->get();
        return response()->json([
            "categories" => Category::all(),
            "categories" => $list
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $rq)
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $rq)
    {

        // $validated = $rq->validate([
        //     'name'=> 'required|string|max:255',
        //     'description'=> 'nullable|string',
        //     'status'=> 'required|boolean',
        //     'parent_id'=> 'nullable|integer|exists:categories,id'
        // ]);

        $rq->validate([
            'name'=> 'required|string|max:255',
            'description'=> 'nullable|string',
            'status'=> 'required|boolean',
            'parent_id'=> 'nullable|integer|exists:categories,id'
        ]);

        Category::create([
            'name'=> $rq->name,
            'description'=> $rq->description,
            'status'=> $rq->status,
            'parent_id' => $rq->parent_id
        ]);
        return response()->json([
            "message" => "Category created successfully",
            
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $categories = Categrory::find($id);
        return response()->json([
            "category" => $category
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $categories = Category::find($id);

        if(!$categories){
            return response()->json([
                "message" => "Category Not Found" 
            ]);
        }
        else{
            $validate = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'required|boolean',
                'parent_id' => 'nullable|integer|exists:categories,id',
            ]);
            $categories->update($validate);

            return response()->json([
                "message" => "Category Updated Successfully",
                "category" => $categories
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $categories = Category::find($id);

        if(!$categories){
            return response()->json([
                "Message" => "Category Not Found"
            ]);
        }
        else{
            $categories->delete();

            return response()->json([
                "message" => "Category Deleted Successfully"
            ]);
        }
    }
}
