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
        // Buat tabel pivot: satu lot bisa punya banyak produk
        Schema::create('batch_lot_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_lot_id')->constrained('batch_lots')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('starting_price', 18, 2)->default(0);
            $table->decimal('reserve_price', 18, 2)->nullable();
            $table->timestamps();

            // Satu produk tidak boleh masuk dua kali di lot yang sama
            $table->unique(['batch_lot_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_lot_products');
    }
};
