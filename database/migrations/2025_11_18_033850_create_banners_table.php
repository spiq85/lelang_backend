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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();

            // 🔹 Judul & Deskripsi
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();

            // 🔹 Gambar utama banner
            // simpan relative path seperti "banners/2025/10/banner1.jpg"
            $table->string('image_path')->nullable();

            // 🔹 Urutan tampil (semakin kecil semakin atas)
            $table->unsignedInteger('position')->default(1);

            // 🔹 Status banner
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');

            // 🔹 Periode tampil (opsional)
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            // 🔹 Admin pembuat & updater
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Index untuk filtering cepat
            $table->index(['status', 'start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
