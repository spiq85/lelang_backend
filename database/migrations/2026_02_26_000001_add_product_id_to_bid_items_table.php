<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bid_items', function (Blueprint $table) {
            // Drop foreign keys that depend on the unique index first
            $table->dropForeign(['bid_set_id']);
            $table->dropForeign(['lot_id']);

            // Now we can safely drop the unique index
            $table->dropUnique(['bid_set_id', 'lot_id']);

            // Add product_id column
            $table->foreignId('product_id')
                ->nullable()
                ->after('lot_id')
                ->constrained('products')
                ->nullOnDelete();

            // Re-add the foreign keys
            $table->foreign('bid_set_id')->references('id')->on('bid_sets')->cascadeOnDelete();
            $table->foreign('lot_id')->references('id')->on('batch_lots')->cascadeOnDelete();

            // New composite unique and index
            $table->unique(['bid_set_id', 'lot_id', 'product_id']);
            $table->index(['lot_id', 'product_id', 'bid_amount']);
        });
    }

    public function down(): void
    {
        Schema::table('bid_items', function (Blueprint $table) {
            $table->dropUnique(['bid_set_id', 'lot_id', 'product_id']);
            $table->dropIndex(['lot_id', 'product_id', 'bid_amount']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['bid_set_id']);
            $table->dropForeign(['lot_id']);
            $table->dropColumn('product_id');

            $table->foreign('bid_set_id')->references('id')->on('bid_sets')->cascadeOnDelete();
            $table->foreign('lot_id')->references('id')->on('batch_lots')->cascadeOnDelete();
            $table->unique(['bid_set_id', 'lot_id']);
        });
    }
};
