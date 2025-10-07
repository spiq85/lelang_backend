<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('auction_batches', function (Blueprint $t) {
            $t->id();

            // Owner batch = seller
            $t->foreignId('seller_id')->constrained('users')
                ->cascadeOnUpdate()->restrictOnDelete();

            // Metadata batch
            $t->text('title')->nullable();
            $t->text('description')->nullable();

            // Jadwal batch (semua lot ikut jendela ini)
            $t->timestamp('start_at')->nullable();
            $t->timestamp('end_at')->nullable();

            // RULES di LEVEL BATCH
            // Simpan sebagai JSON string (lebih fleksibel)
            // Contoh bid_increment_rule (flat): {"type":"flat","step":50000}
            // Contoh bid_increment_rule (tiered): {"type":"tiered","steps":[{"lt":10000000,"step":100000},{"lt":50000000,"step":250000},{"gte":50000000,"step":500000}]}
            $t->json('bid_increment_rule')->nullable();

            // Contoh reserve_rule: {"mode":"undisclosed"} | {"mode":"none"} | {"mode":"disclosed"}
            $t->json('reserve_rule')->nullable();

            // Status batch
            $t->enum('status', ['draft','pending_review','published','closed','cancelled'])->default('draft');

            // Admin pencatat/approver (opsional)
            $t->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $t->timestamps();

            // Index helpful
            $t->index(['seller_id','status']);
            $t->index(['status','start_at','end_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('auction_batches');
    }
};
