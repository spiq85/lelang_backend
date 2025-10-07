// database/migrations/xxxx_xx_xx_create_bid_sets_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bid_sets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained('auction_batches')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // bidder
            $t->timestamp('submitted_at')->useCurrent();
            $t->enum('status', ['pending','valid','rejected','withdrawn'])->default('pending');
            $t->timestamps();

            $t->index(['batch_id','user_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('bid_sets'); }
};
