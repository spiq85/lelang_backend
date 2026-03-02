<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBatch;
use App\Models\Product;
use App\Models\BatchLot;
use App\Models\BatchLotProduct;
use App\Models\LotWinner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerDashboardController extends Controller
{
    /**
     * Get seller dashboard stats
     */
    public function stats(Request $request)
    {
        $sellerId = Auth::id();

        $totalProducts = Product::where('seller_id', $sellerId)->count();
        $activeProducts = Product::where('seller_id', $sellerId)->where('status', 'published')->count();
        
        $totalBatches = AuctionBatch::where('seller_id', $sellerId)->count();
        $activeBatches = AuctionBatch::where('seller_id', $sellerId)->where('status', 'published')->count();
        $pendingBatches = AuctionBatch::where('seller_id', $sellerId)->where('status', 'pending_review')->count();
        $closedBatches = AuctionBatch::where('seller_id', $sellerId)->where('status', 'closed')->count();

        // Total revenue from winning bids on seller's lots
        $totalRevenue = LotWinner::whereHas('lot.batch', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        })->sum('winning_bid_amount');

        $totalWinners = LotWinner::whereHas('lot.batch', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        })->count();

        return response()->json([
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'total_batches' => $totalBatches,
            'active_batches' => $activeBatches,
            'pending_batches' => $pendingBatches,
            'closed_batches' => $closedBatches,
            'total_revenue' => (float) $totalRevenue,
            'total_winners' => $totalWinners,
        ]);
    }

    /**
     * Get seller's products
     */
    public function products(Request $request)
    {
        $sellerId = Auth::id();

        $query = Product::where('seller_id', $sellerId)
            ->with(['images', 'categories'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($w) use ($term) {
                $w->where('product_name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        return response()->json(
            $query->paginate($request->integer('per_page', 12))
        );
    }

    /**
     * Get seller's auction batches
     */
    public function batches(Request $request)
    {
        $sellerId = Auth::id();

        $query = AuctionBatch::where('seller_id', $sellerId)
            ->with([
                'lots.lotProducts.product.images',
                'lots.winner.winner',
            ])
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $batches = $query->paginate($request->integer('per_page', 10));

        $batches->getCollection()->transform(function ($batch) {
            return [
                'id' => $batch->id,
                'title' => $batch->title,
                'description' => $batch->description,
                'start_at' => $batch->start_at,
                'end_at' => $batch->end_at,
                'status' => $batch->status,
                'phase' => $batch->phase,
                'starts_in_seconds' => $batch->starts_in_seconds,
                'ends_in_seconds' => $batch->ends_in_seconds,
                'progress_percent' => $batch->progress_percent,
                'lots_count' => $batch->lots->count(),
                'total_bids' => $batch->bidSets()->count(),
                'lots' => $batch->lots->map(function ($lot) {
                    return [
                        'id' => $lot->id,
                        'lot_number' => $lot->lot_number,
                        'starting_price' => (float) $lot->starting_price,
                        'status' => $lot->status,
                        'products_count' => $lot->lotProducts->count(),
                        'winner' => $lot->winner ? [
                            'winner_name' => $lot->winner->winner?->full_name ?? '-',
                            'winning_bid_amount' => (float) $lot->winner->winning_bid_amount,
                        ] : null,
                    ];
                }),
            ];
        });

        return response()->json([
            'data' => $batches->items(),
            'pagination' => [
                'total' => $batches->total(),
                'per_page' => $batches->perPage(),
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    /**
     * Store a new product for seller
     */
    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'status' => 'nullable|in:draft,published',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);

        $product = Product::create([
            'seller_id' => Auth::id(),
            'product_name' => $data['product_name'],
            'description' => $data['description'] ?? null,
            'base_price' => $data['base_price'],
            'status' => $data['status'] ?? 'draft',
        ]);

        // Attach categories
        if (!empty($data['category_ids'])) {
            foreach ($data['category_ids'] as $catId) {
                $product->categories()->attach($catId);
            }
        }

        // Handle image uploads
        if ($request->hasFile('images')) {
            $sortOrder = 0;
            foreach ($request->file('images') as $image) {
                $path = $image->store('product-images', 'public');
                $product->images()->create([
                    'image_url' => $path,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return response()->json([
            'message' => 'Produk berhasil dibuat',
            'data' => $product->load(['images', 'categories']),
        ], 201);
    }

    /**
     * Update seller's product
     */
    public function updateProduct(Request $request, $id)
    {
        $product = Product::where('seller_id', Auth::id())->findOrFail($id);

        $data = $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'status' => 'nullable|in:draft,published',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $product->update($data);

        if (isset($data['category_ids'])) {
            $product->categories()->sync($data['category_ids']);
        }

        return response()->json([
            'message' => 'Produk berhasil diupdate',
            'data' => $product->load(['images', 'categories']),
        ]);
    }

    /**
     * Delete seller's product
     */
    public function deleteProduct($id)
    {
        $product = Product::where('seller_id', Auth::id())->findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus']);
    }

    /**
     * Store a new auction batch for seller
     */
    public function storeBatch(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'bid_increment_rule' => 'nullable|array',
            'reserve_rule' => 'nullable|array',
        ]);

        $batch = AuctionBatch::create([
            'seller_id' => Auth::id(),
            'title' => $data['title'],
            'description' => $data['description'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'bid_increment_rule' => $data['bid_increment_rule'] ?? [],
            'reserve_rule' => $data['reserve_rule'] ?? [],
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Batch berhasil dibuat',
            'data' => $batch->load('lots'),
        ], 201);
    }

    /**
     * Update seller's batch
     */
    public function updateBatch(Request $request, $id)
    {
        $batch = AuctionBatch::where('seller_id', Auth::id())->findOrFail($id);

        if (!in_array($batch->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Batch tidak dapat diubah dalam status ini'], 422);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'start_at' => 'sometimes|date',
            'end_at' => 'sometimes|date|after:start_at',
            'bid_increment_rule' => 'nullable|array',
            'reserve_rule' => 'nullable|array',
        ]);

        $batch->update($data);

        return response()->json([
            'message' => 'Batch berhasil diupdate',
            'data' => $batch->load('lots'),
        ]);
    }

    /**
     * Submit batch for review
     */
    public function submitForReview($id)
    {
        $batch = AuctionBatch::where('seller_id', Auth::id())->findOrFail($id);

        if (!$batch->canSendToReview()) {
            return response()->json(['message' => 'Batch tidak dapat dikirim untuk review dari status saat ini'], 422);
        }

        $batch->update(['status' => 'pending_review']);

        return response()->json([
            'message' => 'Batch berhasil dikirim untuk review',
            'data' => $batch,
        ]);
    }

    /**
     * Delete seller's batch
     */
    public function deleteBatch($id)
    {
        $batch = AuctionBatch::where('seller_id', Auth::id())->findOrFail($id);

        if (!in_array($batch->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Hanya batch draft/cancelled yang dapat dihapus'], 422);
        }

        $batch->delete();

        return response()->json(['message' => 'Batch berhasil dihapus']);
    }

    /**
     * Get seller's batch detail
     */
    public function showBatch($id)
    {
        $batch = AuctionBatch::where('seller_id', Auth::id())
            ->with([
                'lots.lotProducts.product.images',
                'lots.lotProducts.product.categories',
                'lots.winner.winner',
                'lots.bidItems.bidSet.user',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $batch->id,
                'title' => $batch->title,
                'description' => $batch->description,
                'status' => $batch->status,
                'start_at' => $batch->start_at,
                'end_at' => $batch->end_at,
                'bid_increment_rule' => $batch->bid_increment_rule,
                'reserve_rule' => $batch->reserve_rule,
                'phase' => $batch->phase,
                'review_note' => $batch->review_note,
                'lots' => $batch->lots->map(function ($lot) {
                    return [
                        'id' => $lot->id,
                        'lot_number' => $lot->lot_number,
                        'starting_price' => (float) $lot->starting_price,
                        'reserve_price' => $lot->reserve_price ? (float) $lot->reserve_price : null,
                        'status' => $lot->status,
                        'products' => $lot->lotProducts->map(function ($lp) {
                            $p = $lp->product;
                            return $p ? [
                                'id' => $p->id,
                                'product_name' => $p->product_name,
                                'base_price' => (float) $p->base_price,
                                'starting_price' => (float) ($lp->starting_price ?? 0),
                                'image_url' => $p->images->first()?->image_url ? '/storage/' . $p->images->first()->image_url : null,
                            ] : null;
                        })->filter()->values(),
                        'bids_count' => $lot->bidItems->count(),
                        'highest_bid' => $lot->bidItems->max('bid_amount'),
                        'winner' => $lot->winner ? [
                            'winner_name' => $lot->winner->winner?->full_name ?? '-',
                            'winning_bid_amount' => (float) $lot->winner->winning_bid_amount,
                            'decided_at' => $lot->winner->decided_at,
                        ] : null,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Store a new lot for a batch
     */
    public function storeLot(Request $request, $batchId)
    {
        $batch = AuctionBatch::where('seller_id', Auth::id())->findOrFail($batchId);

        if (!in_array($batch->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Lot hanya bisa ditambahkan pada batch draft'], 422);
        }

        $data = $request->validate([
            'starting_price' => 'required|numeric|min:0',
            'reserve_price' => 'nullable|numeric|min:0',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        // Auto lot number
        $maxLot = $batch->lots()->max('lot_number') ?? 0;

        $lot = BatchLot::create([
            'batch_id' => $batch->id,
            'lot_number' => $maxLot + 1,
            'starting_price' => $data['starting_price'],
            'reserve_price' => $data['reserve_price'] ?? null,
            'status' => 'open',
        ]);

        // Attach products to lot
        foreach ($data['product_ids'] as $productId) {
            // Verify product belongs to seller
            $product = Product::where('seller_id', Auth::id())->find($productId);
            if ($product) {
                BatchLotProduct::create([
                    'batch_lot_id' => $lot->id,
                    'product_id' => $product->id,
                    'starting_price' => $product->base_price,
                ]);
            }
        }

        return response()->json([
            'message' => 'Lot berhasil ditambahkan',
            'data' => $lot->load('lotProducts.product.images'),
        ], 201);
    }

    /**
     * Update a lot
     */
    public function updateLot(Request $request, $batchId, $lotId)
    {
        $batch = AuctionBatch::where('seller_id', Auth::id())->findOrFail($batchId);

        if (!in_array($batch->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Lot tidak bisa diubah pada batch ini'], 422);
        }

        $lot = BatchLot::where('batch_id', $batch->id)->findOrFail($lotId);

        $data = $request->validate([
            'starting_price' => 'sometimes|numeric|min:0',
            'reserve_price' => 'nullable|numeric|min:0',
            'product_ids' => 'sometimes|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        if (isset($data['starting_price'])) {
            $lot->starting_price = $data['starting_price'];
        }
        if (array_key_exists('reserve_price', $data)) {
            $lot->reserve_price = $data['reserve_price'];
        }
        $lot->save();

        // Sync products if provided
        if (isset($data['product_ids'])) {
            $lot->lotProducts()->delete();
            foreach ($data['product_ids'] as $productId) {
                $product = Product::where('seller_id', Auth::id())->find($productId);
                if ($product) {
                    BatchLotProduct::create([
                        'batch_lot_id' => $lot->id,
                        'product_id' => $product->id,
                        'starting_price' => $product->base_price,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Lot berhasil diupdate',
            'data' => $lot->load('lotProducts.product.images'),
        ]);
    }

    /**
     * Delete a lot
     */
    public function deleteLot($batchId, $lotId)
    {
        $batch = AuctionBatch::where('seller_id', Auth::id())->findOrFail($batchId);

        if (!in_array($batch->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Lot tidak bisa dihapus pada batch ini'], 422);
        }

        $lot = BatchLot::where('batch_id', $batch->id)->findOrFail($lotId);
        $lot->lotProducts()->delete();
        $lot->delete();

        return response()->json(['message' => 'Lot berhasil dihapus']);
    }
}
