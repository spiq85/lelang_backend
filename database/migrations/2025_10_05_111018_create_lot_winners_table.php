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
        Schema::create('lot_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('batch_lots')->cascadeOnDelete();
            $table->foreignId('winner_user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('winning_bid_amount', 18,2);
            $table->foreignId('choosen_by')->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->timestamp('decided_at')->useCurrent();
            $table->timestamps();

            $table->unique('lot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_winners');
    }
};
