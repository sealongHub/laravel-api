<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\userRoleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProviceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\MembershipsController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//middleware auth:api
Route::middleware("auth:api")->group(function () {
    Route::get('/role', [userRoleController::class, "index"]);
    Route::post('/role', [userRoleController::class, "store"]);
    Route::get('/role/{id}', [userRoleController::class, "show"]);
    Route::put('/role/{id}', [userRoleController::class, "update"]);
    Route::delete('/role/{id}', [userRoleController::class, "destroy"]);
    //Route for Categories
    Route::apiResource('/categories', CategoryController::class);
    //Route for Provinces
    Route::apiResource('/provinces', ProviceController::class);
    Route::apiResource('/brand', BrandController::class);
    Route::apiResource('/product', ProductController::class);

    // ✅ Add to Cart route
    Route::post('/add-to-cart', [CartController::class, 'addToCart']);
    //Route for memberships
    Route::apiResource('/membership', MembershipsController::class);

    
});
//end middleware auth:api

//testing route localhost: 8000/api/role method get
//Auth routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);