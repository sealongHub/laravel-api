<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Role::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $roles = new role();
        $roles->name = $request->input("name");
        $roles->code = $request->input("code");
        $roles->description = $request->input("description");
        $roles->status = $request->input("status");
        $roles->testing = $request->input("testing");
        $roles->save();

        return [
            "Data" => $roles,
            "message" => "Insert Successfully.."
        //    "request-data: " => $request->input()
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return[
            $roles = role::find($id),
            "message" => "ID ".$id." "." Was Found in system"
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $roles = role::find($id);
        if(!$roles){
            return[
                "Error" => true,
                "message" => "This Id not Found in the system",
            ];
        }else{
                $roles->name = $request->input("name");
                $roles->code = $request->input("code");
                $roles->description = $request->input("description");
                $roles->testing = $request->input("testing");
                $roles->update();
            return[
                "Data" => $roles,
                "message" => "Update successfully",
            ];
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $roles = role::find($id);
        if(!$roles){
            return [
            "Error" => true,
            "Message" => "This Id Not Found in System",
        ];
        }
        return [
            "Data" => $roles,
            "Message" => "Delete Successfully",
            $roles->delete()
        ];
    }

    public function test() {
        return "testing Function";
    }
}