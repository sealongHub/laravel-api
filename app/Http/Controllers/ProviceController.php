<?php

namespace App\Http\Controllers;

use App\Models\Provice;
use Illuminate\Http\Request;

class ProviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $rq)
    {
        $pro = Provice::query();

        if($rq->has('text_search')){
            $pro->where('name', "LIKE", "%".$rq->input('text_search')."%");
        }

        if($rq->has('status')){
            $pro->where('status', "=", $rq->input('status'));
        }

        $proQuery = $pro->get();

        return response()->json([
            "prolist" => Provice::all(),
            "prolist" => $proQuery
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'description' => 'nullable|max:500',
            'distand_from_city' => 'required|numeric',
            'status' => 'required|boolean',
        ]);

        Provice::create($validated);

        return response()->json([
            "message" => "Province created successfully",
            "province" => $validated
        ]); 
        
    }


    /**
     * Display the specified resource.
     */
    public function show(Provice $provice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $rq)
    {
        

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $rq, $id)
    {
        $pro = Provice::find($id);

        if (!$pro) {
            return response()->json([
                "message" => "Province not found"
            ]);
        }

        $validated = $rq->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'description' => 'nullable|max:500',
            'distand_from_city' => 'required|numeric',
            'status' => 'required|boolean',
        ]);

        // Update the record
        $pro->update($validated);

        return response()->json([
            "message" => "Province updated successfully",
            "province" => $pro
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Provice::destroy($id);

        return response()->json([
            "message" => "Province deleted successfully",
            
        ]);
    }
}
