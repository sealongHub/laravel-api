<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; //hash password new account
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;

class AuthController extends Controller
{
    public function register (Request $rq) {
        $rq->validate([
            "name" => "required|string",
            "email" => "required|email|unique:users,email",
            "password" => "required|string|min:6|confirmed",// key password_confirmation
            "phone" => "nullable",
            "address" => "nullable",
            "image" => "nullable|image|mimes:jpeg,jpg,png,gif|max:2048",
            "type" => "nullable",
        ]);

       //create user
        $user = User::create([
            "name" => $rq->name,
            "email" => $rq->email,
            "password" => Hash::make($rq->password),
        ]);


        if($rq->has("image")){
            $imagePath = $rq->file("image")->store("profiles", "public");
        }

        //create profile
        $user->profile()->create([
            "phone" => $rq->phone,
            "address" => $rq->address,
            "image" => $imagePath,
            "type" => $rq->type,
        ]);


        return response()->json([
            "message" => "User Registered Successfully",
            "user" => $user->load('profile'),
        ],201);
    }

    public function login (Request $rq){
        $rq->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        //verify user and create token
        if(!$token = JWTAuth::attempt($rq->only('email', 'password'))){
            return response()->json([
                "error" => "Invalid Credentials"
            ],401);
        }

        return response()->json([
            "message" => "Login Successful",
            "access_token" => $token,
            "users" => JWTAuth::user()->load('profile'),
        ]);
    }
}