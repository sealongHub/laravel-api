<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


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
        'image' => 'nullable|image|max:2048',
    ]);

    $data = $request->all();
    $data['quantity'] = $request->input('quantity', 0);

    try {
        if ($request->hasFile('image')) {
            // ប្រើរបៀបនេះវិញ
            $uploadedFile = $request->file('image');
            $result = Cloudinary::upload($uploadedFile->getRealPath(), [
                'folder' => 'products'
            ]);
            
            // ឆែកមើលថា តើវា upload បានពិតមែនឬអត់
            if ($result) {
                $data['image'] = $result->getSecurePath();
            }
        }

        // បង្កើត Product ក្នុង Database
        $product = Product::create($data);

        return response()->json([
            'message' => 'Product created successfully',
            'test_config' => config('cloudinary.cloud_url'), // បង្ហាញ config ដើម្បីតេស្ត
            'data' => $product,
        ], 201);

    } catch (\Exception $e) {
        // បើគាំងត្រង់ Cloudinary វានឹងប្រាប់សារ Error ច្បាស់ៗមកវិញ
        return response()->json([
            'error' => 'Server Error: ' . $e->getMessage(),
            'hint' => 'Please run php artisan config:clear'
        ], 500);
    }
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    try {
        $pro = Product::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|unique:products,code,'.$id,
            'supplier_id' => 'nullable|integer', // Ensure this matches DB column
            'price'       => 'required|numeric',
            'category_id' => 'required|integer',
            'brand_id'    => 'required|integer',
            'status'      => 'required',
        ]);

        $data = $request->only(['name', 'code', 'price', 'quantity', 'category_id', 'brand_id', 'supplier_id', 'status']);

        // Handle Image Upload
        if ($request->hasFile('image')) {
            if ($pro->image && Storage::disk('public')->exists($pro->image)) {
                Storage::disk('public')->delete($pro->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        // Handle Image Removal
        if ($request->image_remove == 'true') {
            if ($pro->image) {
                Storage::disk('public')->delete($pro->image);
            }
            $data['image'] = null;
        }

        $pro->update($data);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $pro,
        ]);

    } catch (\Exception $e) {
        // This will return the actual error message instead of just "500"
        return response()->json([
            'message' => 'Update failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pro = Product::findOrFail($id);

        if($pro->image){
            Storage::disk('public')->delete($pro->image);
        }

        $pro->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);  
    }
}