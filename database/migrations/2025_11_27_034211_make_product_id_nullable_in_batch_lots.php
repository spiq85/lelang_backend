<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('batch_lots', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')
                ->nullable()
                ->change();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete(); // atau restrictOnDelete jika mau
        });
    }

    public function down()
    {
        Schema::table('batch_lots', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')
                ->constrained('products')
                ->change();
        });
    }
};
