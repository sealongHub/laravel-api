<?php

namespace App\Http\Controllers;

use App\Models\FounderImage;
use Illuminate\Http\Request;

class FounderImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $founderImage = FounderImage::all();

        return response()->json([
            "message" => "Get Founder Images",
            "founderImageList" => $founderImage,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,bmp,webp|max:2048',
            'description' => 'nullable|string',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = uploadImage('founderimages', $request->file('image'));
        }

        $founderImage = FounderImage::create($data);
        return response()->json([
            'message' => 'Founder Image created successfully',
            'data' => $founderImage,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FounderImage $founderImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FounderImage $founderImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $founderImage = FounderImage::find($id);

        $request->validate([
            'title' => 'required|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,bmp,webp|max:2048',
            'description' => 'nullable|string',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $newImage = uploadImage('founderimages', $request->file('image'));
            deleteImage($founderImage->image);
            $data['image'] = $newImage;
        }

        if ($request->boolean('image_remove')) {
            deleteImage($founderImage->image);
            $data['image'] = null;
        }

        $founderImage->update($data);
        return response()->json([
            'message' => 'Founder Image updated successfully',
            'data' => $founderImage,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $founderImageToDelete = FounderImage::find($id);

        deleteImage($founderImageToDelete->image);
        $founderImageToDelete->delete();

        return response()->json([
            'message' => 'Founder Image deleted successfully',
        ], 200);
    }
}
