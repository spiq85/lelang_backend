<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Tambah is_trending KALAU BELUM ADA
            if (!Schema::hasColumn('products', 'is_trending')) {
                $table->boolean('is_trending')->default(false)->after('status');
            }

            // Tambah kolom trending otomatis
            if (!Schema::hasColumn('products', 'trending_at')) {
                $table->timestamp('trending_at')->nullable()->after('is_trending');
            }

            if (!Schema::hasColumn('products', 'trending_order')) {
                $table->unsignedInteger('trending_order')->nullable()->after('trending_at');
            }

            // Index biar query cepet banget
            $table->index(['is_trending', 'trending_order']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_trending', 'trending_order']);
            
            if (Schema::hasColumn('products', 'trending_order')) {
                $table->dropColumn('trending_order');
            }
            if (Schema::hasColumn('products', 'trending_at')) {
                $table->dropColumn('trending_at');
            }
            // Kalau mau hapus is_trending juga, uncomment:
            // if (Schema::hasColumn('products', 'is_trending')) {
            //     $table->dropColumn('is_trending');
            // }
        });
    }
};