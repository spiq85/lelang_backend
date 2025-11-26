<?php
// app/Observers/ProductObserver.php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductObserver
{
    public function updating(Product $product)
    {
        // HANYA JALAN KALAU is_trending berubah DARI false → true
        if ($product->isDirty('is_trending') && $product->is_trending === true && $product->getOriginal('is_trending') === false) {
            
            DB::transaction(function () use ($product) {
                // 1. Set jadi #1
                $product->trending_at = now();
                $product->trending_order = 1;

                // 2. Geser semua ke bawah
                Product::where('is_trending', true)
                    ->where('id', '!=', $product->id)
                    ->increment('trending_order');

                // 3. Kick yang ke-11 (kalau sudah lebih dari 10)
                Product::where('is_trending', true)
                    ->orderBy('trending_order', 'desc')
                    ->skip(9) // ambil dari urutan 10 ke bawah
                    ->take(100)
                    ->update([
                        'is_trending' => false,
                        'trending_at' => null,
                        'trending_order' => null,
                    ]);
            });
        }

        // KALAU DIMATIKAN (true → false) → bersihkan & geser urutan
        if ($product->isDirty('is_trending') && $product->is_trending === false && $product->getOriginal('is_trending') === true) {
            
            $oldOrder = $product->getOriginal('trending_order');

            $product->trending_at = null;
            $product->trending_order = null;

            // Geser yang di bawahnya naik
            Product::where('is_trending', true)
                ->where('trending_order', '>', $oldOrder)
                ->decrement('trending_order');
        }
    }
}