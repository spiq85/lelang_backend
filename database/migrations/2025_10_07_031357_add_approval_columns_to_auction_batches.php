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
        Schema::table('auction_batches', function (Blueprint $t) {
            $t->foreignId('approved_by')->nullable()->after('status')
              ->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable()->after('approved_by');
            $t->text('review_note')->nullable()->after('approved_at'); // alasan approve/reject
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_batches', function (Blueprint $t) {
            $t->dropConstrainedForeignId('approved_by');
            $t->dropColumn(['approved_at','review_note']);
        });
    }
};
