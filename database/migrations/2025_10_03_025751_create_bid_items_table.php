// database/migrations/xxxx_xx_xx_create_bid_items_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bid_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bid_set_id')->constrained('bid_sets')->cascadeOnDelete();
            $t->foreignId('lot_id')->constrained('batch_lots')->cascadeOnDelete();
            $t->decimal('bid_amount', 18, 2);
            $t->boolean('is_proxy')->default(false);
            $t->timestamps();

            $t->unique(['bid_set_id','lot_id']);  // 1 lot hanya sekali diisi pada 1 paket
            $t->index(['lot_id','bid_amount']);   // bantu pencarian pemenang per lot
        });
    }
    public function down(): void { Schema::dropIfExists('bid_items'); }
};
