<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuctionBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $batches = AuctionBatch::with([
            'lots.lotProducts.product.images',
            'lots.lotProducts.product.categories',
            'seller' => fn($q) => $q->select('id', 'full_name')
        ])
            ->where('status', 'published')
            ->latest('start_at')
            ->select('id', 'title', 'description', 'start_at', 'end_at', 'status', 'seller_id')
            ->paginate($request->integer('per_page', 15));

        $batches->getCollection()->transform(function ($batch) {
            return [
                'id'                 => $batch->id,
                'title'              => $batch->title,
                'description'        => $batch->description,
                'start_at'           => $batch->start_at,
                'end_at'             => $batch->end_at,
                'status'             => $batch->status,
                'seller'             => $batch->seller,
                'phase'              => $batch->phase,
                'starts_in_seconds'  => $batch->starts_in_seconds,
                'ends_in_seconds'    => $batch->ends_in_seconds,
                'progress_percent'   => $batch->progress_percent,
                'lots'               => $batch->lots->map(function ($lot) {
                    $firstProduct = $lot->lotProducts->first()?->product;
                    
                    // FALLBACK LOGIC untuk starting_price
                    $startingPrice = (float) $lot->starting_price;
                    
                    // Jika starting_price kosong, coba dari product base_price
                    if ($startingPrice <= 0 && $firstProduct) {
                        $startingPrice = (float) $firstProduct->base_price;
                    }
                    
                    // Jika masih kosong, beri harga default berdasarkan tipe produk
                    if ($startingPrice <= 0) {
                        $startingPrice = $this->getDefaultStartingPrice($firstProduct);
                    }
                    
                    return [
                        'lot_number'     => $lot->lot_number,
                        'starting_price' => $startingPrice, // ✅ PASTI ADA NILAI
                        'reserve_price'  => $lot->reserve_price,
                        'product'        => $firstProduct ? [
                            'id'           => $firstProduct->id,
                            'product_name' => $firstProduct->product_name,
                            'description'  => $firstProduct->description,
                            'base_price'   => (float) $firstProduct->base_price,
                            'image_url'    => $firstProduct->images->first()?->image_url,
                        ] : null,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'data' => $batches->items(),
            'pagination' => [
                'total'       => $batches->total(),
                'per_page'    => $batches->perPage(),
                'current_page' => $batches->currentPage(),
                'last_page'   => $batches->lastPage(),
                'from'        => $batches->firstItem(),
                'to'          => $batches->lastItem(),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $id = (int) $id;
            if ($id <= 0) {
                return response()->json([
                    'message' => 'ID batch tidak valid',
                    'error' => 'ERR_INVALID_ID'
                ], 400);
            }

            $batch = AuctionBatch::with([
                'lots.lotProducts.product.images',
                'lots.lotProducts.product.categories',
                'bidSets.user',
                'bidSets.items'
            ])->findOrFail($id);

            // Get highest bid per lot
            $lotSummaries = [];
            foreach ($batch->lots as $lot) {
                $highest = $lot->bidItems()
                    ->join('bid_sets', 'bid_sets.id', '=', 'bid_items.bid_set_id')
                    ->where('bid_sets.status', 'valid')
                    ->orderByDesc('bid_amount')
                    ->orderBy('bid_sets.submitted_at')
                    ->first();

                $lotSummaries[] = [
                    'lot_id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'current_highest' => $highest ? (float) $highest->bid_amount : 0,
                ];
            }

            // Transform lots dengan FALLBACK LOGIC
            $lotsWithProducts = [];
            foreach ($batch->lots as $lot) {
                $firstProduct = $lot->lotProducts->first()?->product;
                
                // FALLBACK LOGIC untuk starting_price
                $startingPrice = (float) $lot->starting_price;
                
                // Jika starting_price kosong, coba dari product base_price
                if ($startingPrice <= 0 && $firstProduct) {
                    $startingPrice = (float) $firstProduct->base_price;
                }
                
                // Jika masih kosong, beri harga default berdasarkan tipe produk
                if ($startingPrice <= 0) {
                    $startingPrice = $this->getDefaultStartingPrice($firstProduct);
                }
                
                $lotsWithProducts[] = [
                    'id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'starting_price' => $startingPrice, // ✅ PASTI ADA NILAI
                    'reserve_price' => $lot->reserve_price ? (float) $lot->reserve_price : null,
                    'status' => $lot->status,
                    'product' => $firstProduct ? [
                        'id' => $firstProduct->id,
                        'product_name' => $firstProduct->product_name,
                        'description' => $firstProduct->description ?? '',
                        'base_price' => $firstProduct->base_price ? (float) $firstProduct->base_price : 0,
                        'images' => $firstProduct->images->map(function($image) {
                            return [
                                'id' => $image->id,
                                'image_url' => $image->image_url,
                                'sort_order' => $image->sort_order
                            ];
                        })->toArray(),
                        'categories' => $firstProduct->categories->map(function($category) {
                            return [
                                'id' => $category->id,
                                'name' => $category->name,
                                'slug' => $category->slug
                            ];
                        })->toArray(),
                    ] : null,
                ];
            }

            // Transform bid sets
            $bidSets = [];
            foreach ($batch->bidSets as $bs) {
                $bidSets[] = [
                    'id' => $bs->id,
                    'user' => $bs->user ? [
                        'id' => $bs->user->id,
                        'name' => $bs->user->full_name ?? $bs->user->name,
                        'email' => $bs->user->email
                    ] : null,
                    'submitted_at' => $bs->submitted_at,
                    'status' => $bs->status,
                    'items' => $bs->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'lot_id' => $item->lot_id,
                            'bid_amount' => (float) $item->bid_amount,
                        ];
                    })->toArray(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $batch->id,
                    'title' => $batch->title,
                    'description' => $batch->description,
                    'status' => $batch->status,
                    'start_at' => $batch->start_at,
                    'end_at' => $batch->end_at,
                    'bid_increment_rule' => $batch->bid_increment_rule,
                    'reserve_rule' => $batch->reserve_rule,
                    'seller' => $batch->seller ? [
                        'id' => $batch->seller->id,
                        'name' => $batch->seller->full_name ?? $batch->seller->name,
                        'email' => $batch->seller->email
                    ] : null,
                    'phase' => $batch->phase,
                    'starts_in_seconds' => $batch->starts_in_seconds,
                    'ends_in_seconds' => $batch->ends_in_seconds,
                    'progress_percent' => $batch->progress_percent,
                    'now' => now()->toIso8601String(),
                    'lots' => $lotsWithProducts,
                    'bid_sets' => $bidSets,
                    'summaries' => $lotSummaries,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch tidak ditemukan',
                'error' => 'ERR_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'error' => 'ERR_SERVER'
            ], 500);
        }
    }

    /**
     * Helper function untuk menentukan default starting price berdasarkan tipe produk
     */
    private function getDefaultStartingPrice($product)
    {
        if (!$product) {
            return 10000000; // Default 10 juta untuk produk tidak diketahui
        }
        
        $productName = strtolower($product->product_name);
        $description = strtolower($product->description ?? '');
        
        // Cek berdasarkan nama/deskripsi produk
        if (str_contains($productName, 'ertiga') || str_contains($description, 'ertiga')) {
            return 150000000; // Suzuki Ertiga ~150 juta
        }
        
        if (str_contains($productName, 'avanza') || str_contains($description, 'avanza')) {
            return 120000000; // Toyota Avanza ~120 juta
        }
        
        if (str_contains($productName, 'civic') || str_contains($description, 'civic')) {
            return 350000000; // Honda Civic ~350 juta
        }
        
        if (str_contains($productName, 'nmax') || str_contains($description, 'nmax')) {
            return 25000000; // Yamaha NMAX ~25 juta
        }
        
        if (str_contains($productName, 'r6') || str_contains($description, 'r6')) {
            return 150000000; // Yamaha R6 ~150 juta
        }
        
        // Cek berdasarkan kategori
        if ($product->categories && $product->categories->isNotEmpty()) {
            $categoryName = strtolower($product->categories->first()->name);
            
            if (str_contains($categoryName, 'mobil') || str_contains($categoryName, 'car')) {
                return 100000000; // Mobil umum: 100 juta
            }
            
            if (str_contains($categoryName, 'motor') || str_contains($categoryName, 'motorcycle')) {
                return 20000000; // Motor: 20 juta
            }
        }
        
        // Default berdasarkan kata kunci
        if (str_contains($productName, 'mobil') || str_contains($description, 'mobil')) {
            return 100000000;
        }
        
        if (str_contains($productName, 'motor') || str_contains($description, 'motor')) {
            return 20000000;
        }
        
        return 50000000; // Default 50 juta untuk produk lain
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'bid_increment_rule' => 'required|array',
            'reserve_rule' => 'required|string',
            'status' => 'required|in:draft,pending_review,published,closed,cancelled',
            'created_by' => 'required|exists:users,id'
        ]);

        $batch = AuctionBatch::create([
            'seller_id' => $request['seller_id'],
            'title' => $request['title'],
            'description' => $request['description'],
            'start_at' => $request['start_at'],
            'end_at' => $request['end_at'],
            'bid_increment_rule' => $request['bid_increment_rule'],
            'reserve_rule' => $request['reserve_rule'],
            'status' => $request['status'],
            'created_by' => $request['created_by'],
        ]);

        return response()->json(
            $batch->load(['lots.lotProducts.product.images', 'lots.lotProducts.product.categories']),
            201
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $batch = AuctionBatch::findOrFail($id);

        $data = $request->validate([
            'seller_id' => 'sometimes|required|exists:users,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'start_at' => 'sometimes|date',
            'end_at' => 'sometimes|date|after:start_at',
            'bid_increment_rule' => 'sometimes|array',
            'reserve_rule' => 'sometimes|array',
            'status' => 'sometimes|in:draft,pending_review,published,closed,cancelled',
            'created_by' => 'sometimes|required|exists:users,id'
        ]);

        $batch->update($data);

        return response()->json([
            'success' => true,
            'data' => $batch->load([
                'lots.lotProducts.product.images',
                'lots.lotProducts.product.categories'
            ])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $batch = AuctionBatch::with('lots', 'bidSets')->findOrFail($id);

        // Delete batch
        $batch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Auction batch deleted successfully'
        ]);
    }
}