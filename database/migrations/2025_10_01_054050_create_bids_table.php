<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bids', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained('auction_batches')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->decimal('bid_amount', 18, 2);
            $t->timestamp('submitted_at')->useCurrent();
            $t->enum('status', ['valid','rejected','withdrawn'])->default('valid');
            $t->timestamps();


            $t->index(['batch_id', 'bid_amount', 'submitted_at']);
            $t->index(['batch_id', 'user_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('bids');
    }
};
