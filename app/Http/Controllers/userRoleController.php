<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\userRole;

class userRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $rq)
    {
        $roles = userRole::query();

        if($rq->has("text_search")){
            // $roles->where("name", "=", $rq->input("text_search"));
            $roles->where("name", "LIKE", "%".$rq->input("text_search")."%");
        }
        if($rq->has("status")){
            $roles->where("status", "=", $rq->input("status"));
            // $roles->where("name", "LIKE", "%".$rq->input("text_search")."%");
        }

        $list = $roles->get();        

        return response()->json([
            "list" => userRole::all(),
            "list"=> $list
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $roles = new userRole();

        // $roles->name = $request->input("name");
        // $roles->code = $request->input("code");
        // $roles->description = $request->input("description");
        // $roles->status = $request->input("status");
        // $roles->testing = $request->input("testing");
        // $roles->save();
        $validate = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'description' => 'required|nullable|string',
            'status' => 'required|boolean',
            'testing' => 'nullable|string|max:100',
        ]);

        userRole::create($validate);

        return [
        //    "Insert Successfully" => $roles,
           "message" => "Insert Successfully"
        ];

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $roles = userRole::find($id);
        
        if(!$roles){
            return[
                "Message" => "Id Not Found in the System"
            ];
        }
        else{
            return [
                "Your Data: " => $roles
            ];
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $roles = userRole::find($id);

        if(!$roles){
            return [
                "Message: " => "Id not Found in the system"
            ];
        }else{
            $roles->name = $request->input("name");
            $roles->code = $request->input("code");
            $roles->description = $request->input("description");
            $roles->status = $request->input("status");
            $roles->testing = $request->input("testing");
            $roles->update();

            return [
                "After Update: " => $roles,
                "message" => "Update Successfully"
            ];
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $roles = userRole::find($id);
    
        if(!$roles){
            return [
                "Id Delete Not Found in system"
            ];
        }
        else{
            $roles->delete();
            return [
                "Data was Delete: " => $roles,
                "message" => "Delete Successfully"
            ];
        }
    }
}