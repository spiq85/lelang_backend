<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBatch;
use App\Models\Product;

class HomeController extends Controller
{
    public function closed()
    {
        $batches = AuctionBatch::where('status', 'closed')
            ->with(['products.coverImage', 'products.seller'])
            ->orderByDesc('end_at')
            ->limit(10)
            ->get();

        $products = $batches->pluck('products')->flatten()->take(15);

        $cleanProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'base_price' => (int) $product->base_price,
                'image_url' => $product->coverImage?->image_url,
                'city' => $product->seller?->city ?? 'JAKARTA',
            ];
        })->values()->all();

        return response()->json([
            'closed_auction' => $cleanProducts  // ← LANGSUNG ARRAY PRODUK, BUKAN BATCH!
        ]);
    }

    public function trending()
    {
        $trending = Product::where('is_trending', true)
            ->where('status', 'published')
            ->with(['images', 'seller'])
            ->withCount(['bidItems as bids_count'])
            ->orderBy('trending_order')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                $product->images->transform(function ($image) {
                    if ($image->image_url && !str_starts_with($image->image_url, '/storage/') && !str_starts_with($image->image_url, 'http')) {
                        $image->image_url = '/storage/' . $image->image_url;
                    }
                    return $image;
                });
                return $product;
            });

        return response()->json([
            'trending' => $trending
        ]);
    }
}