<?php

namespace App\Http\Controllers;

use App\Models\Memberships;
use Illuminate\Http\Request;

class MembershipsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $rq)
    {
        $query = Memberships::query();
        if($rq->has("text_search")){
            $query->where("membership_name", "LIKE", $rq->input("membership_name")."%");
        }

        $meb = $query->get();

        return response()->json([
            "memberships" => Memberships::all(),
            "memberships" => $meb
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
            'membership_name' => 'required|string|max:100|unique:memberships,membership_name',
            'description'     => 'nullable|string',
            'discount_rate'   => 'required|numeric|min:0|max:100',
            'monthly_fee'     => 'required|numeric|min:0',
            'min_spent'       => 'required|numeric|min:0',
            'max_spent'       => 'nullable|numeric|min:0',
            'reward_points'   => 'required|integer|min:0',
            'status'          => 'required|in:Active,Inactive',
        ]);

        $data = $request->all();

        $meb = Memberships::create($data);

        return response()->json([
            "message" => "Insert Memberships Successfully...",
            "members" => $meb,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(memberships $memberships)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(memberships $memberships)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $memberFound = Memberships::find($id);

        $request->validate([
            'membership_name' => 'required|string|max:100',
            'description'     => 'nullable|string',
            'discount_rate'   => 'required|numeric|min:0|max:100',
            'monthly_fee'     => 'required|numeric|min:0',
            'min_spent'       => 'required|numeric|min:0',
            'max_spent'       => 'nullable|numeric|min:0',
            'reward_points'   => 'required|integer|min:0',
            'status'          => 'required|in:Active,Inactive',
        ]);

        $data = $request->all();

        $memberFound->update($data);

        return response()->json([
            "message" => "Update Memberships Successfully...",
            "members" => $memberFound,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(memberships $memberships)
    {
        //
    }
}