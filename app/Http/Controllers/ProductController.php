<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;


class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 🎯 Collect filters only once
        $filters = $request->only(['text_search', 'status', 'category_id', 'brand_id']);

        // 🧠 Build main query
        $query = Product::with(['category:id,name', 'brand:id,name', 'supplier:id,name'])
            ->select('id', 'name', 'code',  'price', 'quantity', 'category_id', 'brand_id', 'supplier_id', 'status', 'image', 'created_at')
            ->when($filters['text_search'] ?? null, fn($q, $v) =>
                $q->where(fn($sub) =>
                    $sub->where('name', 'like', "%$v%")
                        ->orWhere('code', 'like', "%$v%")
                )
            )
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['category_id'] ?? null, fn($q, $v) => $q->where('category_id', $v))
            ->when($filters['brand_id'] ?? null, fn($q, $v) => $q->where('brand_id', $v))
            ->when($filters['supplier_id'] ?? null, fn($q, $v) => $q->where('supplier_id', $v))
            ->latest('id');

        // ⚡ Use simplePaginate (no total count query => 2x faster)
        $products = $query->simplePaginate(10);

        // 🔍 Check if searching
        $isSearching = collect($filters)->filter()->isNotEmpty();

        // 🧭 Cache static data (Category + Brand) for 1 hour
        $category = $isSearching ? [] : Cache::remember('categories_list', 3600, fn() =>
            Category::select('id', 'name')->orderBy('name')->get()
        );
        $brand = $isSearching ? [] : Cache::remember('brands_list', 3600, fn() =>
            Brand::select('id', 'name')->orderBy('name')->get()
        );
        $supplier = $isSearching ? [] : Cache::remember('suppliers_list', 3600, fn() =>
            Supplier::select('id', 'name')->orderBy('name')->get()
        );

        // 🚀 Return JSON response (clean & structured)
        return response()->json([
            'proList' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'next_page_url' => $products->nextPageUrl(),
                'prev_page_url' => $products->previousPageUrl(),
                'per_page' => $products->perPage(),
            ],
            'category' => $category,
            'brand' => $brand,
            'supplier' => $supplier
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:100',
        'code' => 'required|unique:products,code',
        'price' => 'required|numeric',
        'category_id' => 'required|exists:categories,id',
        'brand_id' => 'required|exists:brands,id',
        'status' => 'required', // ដក boolean ចេញសិន បើផ្ញើពី FormData អាចជា string "1"
        'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,bmp,webp|max:2048',
    ]);

    $data = $request->except('image');
    $data['quantity'] = $request->input('quantity', 0);

    if ($request->hasFile('image')) {
        $data['image'] = uploadImage('products', $request->file('image'));
    }

    $product = Product::create($data);

    return response()->json([
        'message' => 'Product created successfully',
        'data' => $product,
    ], 201);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    $pro = Product::findOrFail($id);

    $request->validate([
        'name'        => 'required|string|max:100',
        'code'        => 'required|unique:products,code,'.$id,
        'supplier_id' => 'nullable|integer',
        'price'       => 'required|numeric',
        'category_id' => 'required|integer',
        'brand_id'    => 'required|integer',
        'status'      => 'required',
        'image'       => 'nullable|image|mimes:jpeg,jpg,png,gif,bmp,webp|max:2048',
    ]);

    $data = $request->only(['name', 'code', 'price', 'quantity', 'category_id', 'brand_id', 'supplier_id', 'status']);
    $previousImage = $pro->image;

    if ($request->hasFile('image')) {
        $data['image'] = uploadImage('products', $request->file('image'));
    } elseif ($request->boolean('image_remove')) {
        $data['image'] = null;
    }

    $pro->update($data);

    if (array_key_exists('image', $data) && $previousImage && $previousImage !== $pro->image) {
        deleteImage($previousImage);
    }

    return response()->json([
        'message' => 'Product updated successfully',
        'data' => $pro,
    ]);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pro = Product::findOrFail($id);

        deleteImage($pro->image);

        $pro->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);  
    }
}