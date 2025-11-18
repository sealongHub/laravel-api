<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provices', function (Blueprint $table) {
            $table->id();
            $table->string('name', length:50); //required
            $table->string('code', length:10); //required
            $table->text('description')->nullable();
            $table->double('distand_from_city')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provices');
    }
};
