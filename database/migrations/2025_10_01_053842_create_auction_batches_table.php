<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('auction_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('seller_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $t->text('title')->nullable();
            $t->text('description')->nullable();
            $t->text('bid_increment_rule')->nullable(); 
            $t->text('reserve_rule')->nullable();       
            $t->decimal('starting_price', 18, 2);
            $t->decimal('reserve_price', 18, 2)->nullable();
            $t->enum('status', ['draft','active','closed','cancelled'])->default('draft');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->timestamp('updated_at')->useCurrent()->nullable();

            $t->index(['seller_id','status']);
            $t->index(['product_id','status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('auction_batches');
    }
};
