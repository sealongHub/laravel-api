<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;

use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\OrdersController;

class OrderCancellationTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function normal_cancellation_restores_stock_and_logs_transaction()
    {
        // create category and product
        $category = \App\Models\Category::create(['name' => 'Default']);
        $brand = \App\Models\Brand::create(['name' => 'Default Brand', 'code' => 'DFB']);
        $supplier = \App\Models\Supplier::create(['name' => 'Default Supplier']);
        $product = Product::create([ 'name' => 'Test Product', 'code' => 'TP-001', 'price' => 10, 'quantity' => 8, 'category_id' => $category->id, 'brand_id' => $brand->id, 'supplier_id' => $supplier->id ]);

        // create order with pending status and detail qty 1
        $order = Order::create([ 'buyer_name' => 'C', 'buyer_phone' => '0123', 'address' => 'A', 'province_id' => 1, 'payment_method' => 'aba', 'total' => 10, 'status' => 'pending' ]);

        DB::table('order_details')->insert([ 'order_id' => $order->id, 'product_id' => $product->id, 'price' => 10, 'quantity' => 1, 'subtotal' => 10, 'created_at' => now(), 'updated_at' => now() ]);

        // simulate reservation that store() would do
        $product->decrement('quantity', 1);
        DB::table('stock_transactions')->insert([ 'product_id' => $product->id, 'quantity' => 1, 'type' => 'out', 'reason' => "Reserved by Order #{$order->id}", 'created_at' => now(), 'updated_at' => now() ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 7]);

        // call controller to cancel
        $req = Request::create('/orders/'.$order->id.'/status', 'PUT', ['status' => 'cancelled']);
        $controller = new OrdersController();
        $response = $controller->updateStatus($req, $order->id);

        // product should be restored
        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 8]);

        // stock_transactions should have an 'in' entry
        $this->assertDatabaseHas('stock_transactions', ['product_id' => $product->id, 'quantity' => 1, 'type' => 'in']);
    }

    /** @test */
    public function double_cancellation_is_idempotent_and_restores_only_once()
    {
        $category = \App\Models\Category::create(['name' => 'Default']);
        $brand = \App\Models\Brand::create(['name' => 'Default Brand', 'code' => 'DFB']);
        $supplier = \App\Models\Supplier::create(['name' => 'Default Supplier']);
        $product = Product::create([ 'name' => 'Test Product', 'code' => 'TP-001', 'price' => 10, 'quantity' => 8, 'category_id' => $category->id, 'brand_id' => $brand->id, 'supplier_id' => $supplier->id ]);

        $order = Order::create([ 'buyer_name' => 'C', 'buyer_phone' => '0123', 'address' => 'A', 'province_id' => 1, 'payment_method' => 'aba', 'total' => 10, 'status' => 'pending' ]);

        DB::table('order_details')->insert([ 'order_id' => $order->id, 'product_id' => $product->id, 'price' => 10, 'quantity' => 1, 'subtotal' => 10, 'created_at' => now(), 'updated_at' => now() ]);

        $product->decrement('quantity', 1);
        DB::table('stock_transactions')->insert([ 'product_id' => $product->id, 'quantity' => 1, 'type' => 'out', 'reason' => "Reserved by Order #{$order->id}", 'created_at' => now(), 'updated_at' => now() ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 7]);

        $req = Request::create('/orders/'.$order->id.'/status', 'PUT', ['status' => 'cancelled']);
        $controller = new OrdersController();
        $controller->updateStatus($req, $order->id);

        // second cancellation attempt
        $controller->updateStatus($req, $order->id);

        // product quantity should be restored to 8 only once
        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 8]);

        // there should be exactly one 'in' entry for this order product
        $countIn = DB::table('stock_transactions')->where('product_id', $product->id)->where('type', 'in')->count();
        $this->assertEquals(1, $countIn);
    }

    /** @test */
    public function qr_reject_flow_cancels_and_restores_stock()
    {
        $category = \App\Models\Category::create(['name' => 'Default']);
        $brand = \App\Models\Brand::create(['name' => 'Default Brand', 'code' => 'DFB']);
        $supplier = \App\Models\Supplier::create(['name' => 'Default Supplier']);
        $product = Product::create([ 'name' => 'QR Product', 'code' => 'QR-001', 'price' => 20, 'quantity' => 5, 'category_id' => $category->id, 'brand_id' => $brand->id, 'supplier_id' => $supplier->id ]);

        $order = Order::create([ 'buyer_name' => 'QR', 'buyer_phone' => '012', 'address' => 'A', 'province_id' => 1, 'payment_method' => 'aba', 'total' => 20, 'status' => 'awaiting_payment' ]);

        DB::table('order_details')->insert([ 'order_id' => $order->id, 'product_id' => $product->id, 'price' => 20, 'quantity' => 2, 'subtotal' => 40, 'created_at' => now(), 'updated_at' => now() ]);

        // simulate reservation
        $product->decrement('quantity', 2);
        DB::table('stock_transactions')->insert([ 'product_id' => $product->id, 'quantity' => 2, 'type' => 'out', 'reason' => "Reserved by Order #{$order->id}", 'created_at' => now(), 'updated_at' => now() ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 3]);

        $req = Request::create('/orders/'.$order->id.'/status', 'PUT', ['status' => 'cancelled']);
        $controller = new OrdersController();
        $controller->updateStatus($req, $order->id);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 5]);
        $this->assertDatabaseHas('stock_transactions', ['product_id' => $product->id, 'quantity' => 2, 'type' => 'in']);
    }
}
