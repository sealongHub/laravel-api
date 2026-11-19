<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('buyer_name');
            $table->string('buyer_phone');
            $table->string('address');
            $table->unsignedBigInteger('province_id');
            $table->text('note')->nullable();
            $table->string('payment_method');
            $table->decimal('total', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->string('transaction_image')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->string('type');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        DB::table('provinces')->insert(['name' => 'Test Province']);
        Storage::fake('public');
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => 'test-chat',
            'services.vercel_blob.token' => 'test-blob-token',
            'services.vercel_blob.store_id' => 'store_test',
        ]);
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
            'https://blob.vercel-storage.com/*' => Http::response([
                'url' => 'https://test.public.blob.vercel-storage.com/transactions/slip.png',
            ]),
        ]);
    }

    public function test_purchase_uses_database_prices_and_decrements_stock(): void
    {
        $product = Product::create([
            'name' => 'Test item',
            'code' => 'TEST-1',
            'price' => 5,
            'quantity' => 5,
        ]);

        $response = $this->post('/purchase', [
            'buyer_name' => 'Test Buyer',
            'buyer_phone' => '012345678',
            'address' => 'Test address',
            'province_id' => 1,
            'payment_method' => 'ABA',
            'total' => 999,
            'delivery_fee' => 2,
            'items' => json_encode([['id' => $product->id, 'qty' => 2]]),
            'transaction_image' => UploadedFile::fake()->image('slip.png'),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order_id', 1);

        $this->assertSame(3, (int) $product->fresh()->quantity);
        $this->assertSame(12.0, (float) DB::table('orders')->value('total'));
        $this->assertSame(10.0, (float) DB::table('order_details')->value('subtotal'));
        $this->assertSame('out', DB::table('stock_transactions')->value('type'));
        $this->assertSame(
            'https://test.public.blob.vercel-storage.com/transactions/slip.png',
            DB::table('orders')->value('transaction_image')
        );
        Storage::disk('public')->assertExists('receipts/receipt_1.pdf');
    }

    public function test_purchase_rejects_malformed_cart_items_without_creating_an_order(): void
    {
        $response = $this->postJson('/purchase', [
            'buyer_name' => 'Test Buyer',
            'buyer_phone' => '012345678',
            'address' => 'Test address',
            'province_id' => 1,
            'payment_method' => 'aba',
            'total' => 5,
            'items' => '[invalid json',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('items');
        $this->assertSame(0, DB::table('orders')->count());
    }
}
