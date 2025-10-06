<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('batch_lots', function (Blueprint $t) {
            $t->id();

            $t->foreignId('batch_id')->constrained('auction_batches')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products')->restrictOnDelete();

            // Penomoran lot unik per batch (Lot #1, #2, ...)
            $t->unsignedInteger('lot_number');

            // ANGKA per-lot (bukan di batch)
            $t->decimal('starting_price', 18, 2);
            $t->decimal('reserve_price', 18, 2)->nullable();

            $t->enum('status', ['open','closed','awarded','settled'])->default('open');

            $t->timestamps();

            $t->unique(['batch_id','lot_number']); // mencegah dua Lot #1 di batch yang sama
            $t->index(['batch_id','status']);
            $t->index('product_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('batch_lots');
    }
};
