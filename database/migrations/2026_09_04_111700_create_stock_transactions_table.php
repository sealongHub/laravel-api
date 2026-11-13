<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('stock_transactions')) {
            Schema::create('stock_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_id')->index();
                $table->integer('quantity');
                $table->enum('type', ['in', 'out'])->default('out');
                $table->string('reason')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                // optional foreign key if products table uses id as unsignedBigInteger
                // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_transactions');
    }
};
