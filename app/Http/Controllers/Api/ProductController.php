<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBatch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function live(Request $request)
    {
        $now = now();

        $query = Product::query()
            ->where('products.status','published')
            ->whereHas('batchLots.batch',function ($b) use ($now) {
                $b->where('auction_batches.status', 'published')
                    ->where('auction_batches.start_at', '<=', $now)
                    ->where('auction_batches.end_at', '>=', $now);
            })
            ->with([
                'images',
                'categories',
                'batches' => fn($q) => $q 
                    ->select(
                        'auction_batches.id', 
                        'auction_batches.title', 
                        'auction_batches.start_at', 
                        'auction_batches.end_at', 
                        'auction_batches.status'
                    )
                    ->where('auction_batches.status', 'published'),
            ]);

            if ($request->filled('category')) {
                $slug = $request->string('category');
                $query->whereHas('categories', fn($q) => $q->where('slug', $slug));
            }

            if ($request->filled('q')) {
                $term = $request->string('q');
                $query->where(function($w) use ($term){
                    $w->where('product_name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            }

            $query->latest('products.created_at');

            return response()->json(
                $query->paginate($request->get('per_page', 12))
            );
    }

    public function detail(Product $product)
{
    $now = now();

    // eager load agar ga nembak query berkali-kali
    $product->load(['images', 'categories', 'batches']);

    // cari batch yang lagi jalan
    $onGoingBatch = $product->batches()
        ->where('auction_batches.status', 'published')
        ->where('auction_batches.start_at', '<=', $now)
        ->where('auction_batches.end_at', '>=', $now)
        ->orderBy('auction_batches.start_at')
        ->first();

    // kalau ga ada yang jalan, ambil batch berikutnya
    $nextBatch = null;
    if (!$onGoingBatch) {
        $nextBatch = $product->batches()
            ->where('auction_batches.status', 'published')
            ->where('auction_batches.start_at', '>', $now)
            ->orderBy('auction_batches.start_at')
            ->first();
    }

    // tentuin batch aktif
    $activeBatch = $onGoingBatch ?: $nextBatch;

    // ambil lot hanya kalau ada batch aktif
    $lots = collect();
    if ($activeBatch) {
        $lots = $product->batchLots()
            ->where('batch_id', $activeBatch->id)
            ->orderBy('lot_number')
            ->get([
                'id',
                'batch_id',
                'product_id',
                'lot_number',
                'starting_price',
                'reserve_price',
                'status',
            ]);
    }

    // meta data batch aktif
    $meta = null;
    if ($activeBatch) {
        $meta = [
            'batch_id' => $activeBatch->id,
            'title' => $activeBatch->title,
            'status' => $activeBatch->status,
            'start_at' => $activeBatch->start_at,
            'end_at' => $activeBatch->end_at,
            'is_ongoing' => $activeBatch->start_at <= $now && $now <= $activeBatch->end_at,
            'start_in_seconds' => $now->lt($activeBatch->start_at)
                ? $now->diffInSeconds($activeBatch->start_at)
                : 0,
            'end_in_seconds' => $now->lt($activeBatch->end_at)
                ? $now->diffInSeconds($activeBatch->end_at)
                : 0,
        ];
    }

    return response()->json([
        'product' => $product,
        'active_batch' => $meta,
        'lots' => $lots,
    ], 200);
}


    public function index(Request $request)
    {
        $query = Product::with(['images', 'categories', 'auctionBatches']);

        // Filter by category
        if ($request->has('category')) {
            $categorySlug = $request->category;
            $query->whereHas('categories', function($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // Search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where('product_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('description', 'like', '%' . $searchTerm . '%');
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|exists:users,id',
            'product_name' => 'required|string|max:255',
            'description' => 'required|string',
            'base_price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,published,archived',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create product
        $productData = $request->only([
            'seller_id', 'product_name', 'description', 'base_price', 'status'
        ]);

        $product = Product::create($productData);

        // Attach categories
        if ($request->has('categories') && is_array($request->categories)) {
            $product->categories()->attach($request->categories);
        }

        // Handle images upload if provided
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('product_images', 'public');
                $product->images()->create([
                    'image_url' => $path,
                    'sort_order' => $index
                ]);
            }
        }

        // Load relationships
        $product->load(['images', 'categories']);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['images', 'categories', 'auctionBatches'])->findOrFail($id);
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'seller_id' => 'sometimes|required|exists:users,id',
            'product_name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'base_price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:draft,published,archived',
            'categories' => 'sometimes|required|array',
            'categories.*' => 'exists:categories,id',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update product data
        $product->update($request->only([
            'seller_id', 'product_name', 'description', 'base_price', 'status'
        ]));

        // Update categories if provided
        if ($request->has('categories') && is_array($request->categories)) {
            $product->categories()->sync($request->categories);
        }

        // Handle images upload if provided
        if ($request->hasFile('images')) {
            // Delete old images
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_url);
                $image->delete();
            }

            // Upload new images
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('product_images', 'public');
                $product->images()->create([
                    'image_url' => $path,
                    'sort_order' => $index
                ]);
            }
        }

        // Load relationships
        $product->load(['images', 'categories']);

        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete images from storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_url);
        }

        // Delete product categories relationship
        $product->categories()->detach();

        // Delete product
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
