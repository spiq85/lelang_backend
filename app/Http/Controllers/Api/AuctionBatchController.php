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
                    return [
                        'lot_number'     => $lot->lot_number,
                        'starting_price' => $lot->starting_price,
                        'product'        => $firstProduct ? [
                            'id'           => $firstProduct->id,
                            'product_name' => $firstProduct->product_name,
                            'image_url'    => $firstProduct->images->first()?->image_url,
                        ] : null,
                    ];
                })->values(), // biar index mulai dari 0 lagi
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
            $lotSummaries = $batch->lots->map(function ($lot) {
                $highest = $lot->bidItems()
                    ->join('bid_sets', 'bid_sets.id', '=', 'bid_items.bid_set_id')
                    ->where('bid_sets.status', 'valid')
                    ->orderByDesc('bid_amount')
                    ->orderBy('bid_sets.submitted_at')
                    ->first();

                return [
                    'lot_id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'current_highest' => $highest?->bid_amount,
                ];
            });

            // Transform lots to include product details
            $lotsWithProducts = $batch->lots->map(function ($lot) {
                // Get first product from lotProducts if exists
                $firstProduct = $lot->lotProducts->first()?->product;
                
                return [
                    'id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'starting_price' => $lot->starting_price,
                    'reserve_price' => $lot->reserve_price,
                    'status' => $lot->status,
                    'product' => $firstProduct ? [
                        'id' => $firstProduct->id,
                        'product_name' => $firstProduct->product_name,
                        'description' => $firstProduct->description ?? null,
                        'images' => $firstProduct->images ?? [],
                        'categories' => $firstProduct->categories ?? [],
                    ] : null,
                ];
            });

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
                    'seller' => $batch->seller,
                    'phase' => $batch->phase,
                    'starts_in_seconds' => $batch->starts_in_seconds,
                    'ends_in_seconds' => $batch->ends_in_seconds,
                    'progress_percent' => $batch->progress_percent,
                    'now' => now()->toIso8601String(),
                    'lots' => $lotsWithProducts,
                    'bid_sets' => $batch->bidSets->map(function ($bs) {
                        return [
                            'id' => $bs->id,
                            'user' => $bs->user,
                            'submitted_at' => $bs->submitted_at,
                            'status' => $bs->status,
                            'items' => $bs->items->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'lot_id' => $item->lot_id,
                                    'bid_amount' => $item->bid_amount,
                                ];
                            }),
                        ];
                    }),
                    'summaries' => $lotSummaries,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Batch tidak ditemukan',
                'error' => 'ERR_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'error' => 'ERR_SERVER'
            ], 500);
        }
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

        return response()->json(
            $batch->load([
                'lots.lotProducts.product.images',
                'lots.lotProducts.product.categories'
            ])
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $batch = AuctionBatch::with('lots', 'bidSets')->findOrFail($id);

        // Delete batch
        $batch->delete();

        return response()->json(['message' => 'Auction batch deleted successfully']);
    }
}
