<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FounderImageController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:api')->group(function () {
    // Role management - admin only
    Route::get('role', [RoleController::class, 'index'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::post('role', [RoleController::class, 'store'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::get('role/{id}', [RoleController::class, 'show'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::put('role/{id}', [RoleController::class, 'update'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::delete('role/{id}', [RoleController::class, 'destroy'])->middleware(\App\Http\Middleware\EnsureAdmin::class);

    // Categories (explicit routes so we can apply role middleware per action)
    Route::get('category', [CategoryController::class, 'index']);
    Route::get('category/{id}', [CategoryController::class, 'show']);
    Route::post('category', [CategoryController::class, 'store'])->middleware(\App\Http\Middleware\EnsureRole::class . ':sales_staff,product_staff,manager,super_admin');
    Route::put('category/{id}', [CategoryController::class, 'update'])->middleware(\App\Http\Middleware\EnsureRole::class . ':product_staff,manager,super_admin');
    Route::delete('category/{id}', [CategoryController::class, 'destroy'])->middleware(\App\Http\Middleware\EnsureRole::class . ':super_admin');

    // Brands
    Route::get('brand', [BrandController::class, 'index']);
    Route::get('brand/{id}', [BrandController::class, 'show']);
    Route::post('brand', [BrandController::class, 'store'])->middleware(\App\Http\Middleware\EnsureRole::class . ':sales_staff,product_staff,manager,super_admin');
    Route::put('brand/{id}', [BrandController::class, 'update'])->middleware(\App\Http\Middleware\EnsureRole::class . ':product_staff,manager,super_admin');
    Route::delete('brand/{id}', [BrandController::class, 'destroy'])->middleware(\App\Http\Middleware\EnsureRole::class . ':super_admin');

    // Orders
    Route::post('/orders', [OrdersController::class, 'store'])->middleware(\App\Http\Middleware\EnsureRole::class . ':sales_staff,product_staff,manager,super_admin');
    Route::get('/orders', [OrdersController::class, 'index']);
    Route::get('/orders/{id}', [OrdersController::class, 'show']);
    Route::put('orders/{id}/status', [App\Http\Controllers\OrdersController::class, 'updateStatus']);

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'stats']);
    Route::get('/dashboardv1', [App\Http\Controllers\DashboardController::class, 'test']);
    Route::post('/stock-in', [StockController::class, 'stockIn'])->middleware(\App\Http\Middleware\EnsureRole::class . ':sales_staff,product_staff,manager,super_admin');
    Route::get('stock-transaction', [StockController::class, 'history']);

    // Suppliers
    Route::get('supplier', [SupplierController::class, 'index']);
    Route::get('supplier/{id}', [SupplierController::class, 'show']);
    Route::post('supplier', [SupplierController::class, 'store'])->middleware(\App\Http\Middleware\EnsureRole::class . ':sales_staff,product_staff,manager,super_admin');
    Route::put('supplier/{id}', [SupplierController::class, 'update'])->middleware(\App\Http\Middleware\EnsureRole::class . ':product_staff,manager,super_admin');
    Route::delete('supplier/{id}', [SupplierController::class, 'destroy'])->middleware(\App\Http\Middleware\EnsureRole::class . ':super_admin');

    Route::get('supplier-list', [SupplierController::class, 'getSupplierList']);
    Route::get('/reports', [ReportController::class, 'orderReport']);
    Route::get('/reports/pdf', [ReportController::class,'exportPdf']);
    Route::get('reports/excel', [ReportController::class, 'exportExcel']);

    // User management - only admin allowed to manage users
    Route::get('users',        [UserController::class, 'index'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::put('users/{id}',   [UserController::class, 'update'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
    Route::delete('users/{id}',[UserController::class, 'destroy'])->middleware(\App\Http\Middleware\EnsureAdmin::class);
});


Route::apiResource('provinces', App\Http\Controllers\ProvincesController::class);
// Product routes with role-based access control
// Public: allow browsing products without authentication
Route::get('product', [ProductController::class, 'index']);
Route::get('product/{id}', [ProductController::class, 'show']);
// Protected for create/update/delete
Route::post('product', [ProductController::class, 'store'])->middleware(\App\Http\Middleware\EnsureRole::class . ':product_staff,manager,super_admin');
Route::put('product/{id}', [ProductController::class, 'update'])->middleware(\App\Http\Middleware\EnsureRole::class . ':product_staff,manager,super_admin');
Route::delete('product/{id}', [ProductController::class, 'destroy'])->middleware(\App\Http\Middleware\EnsureRole::class . ':super_admin');
Route::get('founderimage', [FounderImageController::class, 'index']);
Route::apiResource('founderimage', FounderImageController::class)
    ->except(['index'])
    ->middleware(\App\Http\Middleware\EnsureAdmin::class);


Route::post('purchase', [App\Http\Controllers\purchaseController::class, 'store']);
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('verify-2fa', [AuthController::class, 'verifyTwoFactor']);
