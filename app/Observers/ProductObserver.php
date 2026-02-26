<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductObserver
{
    public function updating(Product $product)
    {
        if ($product->isDirty('is_trending') && $product->is_trending === true && $product->getOriginal('is_trending') === false) {
            
            DB::transaction(function () use ($product) {
                $product->trending_at = now();
                $product->trending_order = 1;

                Product::where('is_trending', true)
                    ->where('id', '!=', $product->id)
                    ->increment('trending_order');

                // Cap trending ke max 9: ambil ID produk posisi 10+ lalu update
                $idsToRemove = Product::where('is_trending', true)
                    ->where('id', '!=', $product->id)
                    ->orderBy('trending_order')
                    ->skip(9)
                    ->take(100)
                    ->pluck('id');

                if ($idsToRemove->isNotEmpty()) {
                    Product::whereIn('id', $idsToRemove)->update([
                        'is_trending' => false,
                        'trending_at' => null,
                        'trending_order' => null,
                    ]);
                }
            });
        }

        if ($product->isDirty('is_trending') && $product->is_trending === false && $product->getOriginal('is_trending') === true) {
            
            $oldOrder = $product->getOriginal('trending_order');

            $product->trending_at = null;
            $product->trending_order = null;

            Product::where('is_trending', true)
                ->where('trending_order', '>', $oldOrder)
                ->decrement('trending_order');
        }
    }
}