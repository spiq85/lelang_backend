<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBatch;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuctionBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $batches = AuctionBatch::with(['product.images', 'product.categories'])
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($batches);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'bid_increment_rule' => 'required|string',
            'reserve_rule' => 'required|string',
            'starting_price' => 'required|numeric|min:0',
            'reserve_price' => 'required|numeric|min:0',
            'status' => 'draft',
            'created_by' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $batch = AuctionBatch::create($request->all());

        return response()->json($batch->load(['product.images', 'product.categories']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $batch = AuctionBatch::with(['product.images', 'product.categories', 'bids.user'])
            ->findOrFail($id);

        // Get highest bid
        $highestBid = $batch->bids()->isValid()->orderByDesc('bid_amount')->first();

        // If authenticated, get user's bid for this batch
        $userBid = null;
        if (auth()->check()) {
            $userBid = $batch->bids()->where('user_id', auth()->id())->first();
        }

        return response()->json([
            'batch' => $batch,
            'highest_bid' => $highestBid,
            'user_bid' => $userBid
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $batch = AuctionBatch::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'seller_id' => 'sometimes|required|exists:users,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'bid_increment_rule' => 'sometimes|required|string',
            'reserve_rule' => 'sometimes|required|string',
            'starting_price' => 'sometimes|required|numeric|min:0',
            'reserve_price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:draft,active,closed,cancelled',
            'created_by' => 'sometimes|required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $batch->update($request->all());

        return response()->json($batch->load(['product.images', 'product.categories']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $batch = AuctionBatch::findOrFail($id);

        // Delete all bids related to this batch
        $batch->bids()->delete();

        // Delete batch
        $batch->delete();

        return response()->json(['message' => 'Auction batch deleted successfully']);
    }

    /**
     * Place a bid on an auction batch.
     */
    public function placeBid(Request $request, $id)
    {
        $request->validate([
            'bid_amount' => 'required|numeric|min:0'
        ]);

        $batch = AuctionBatch::findOrFail($id);

        // Check if batch is still active
        if ($batch->status !== 'active') {
            return response()->json([
                'message' => 'Auction for this batch is closed'
            ], 400);
        }

        // Get current highest bid
        $highestBid = $batch->bids()->isValid()->orderByDesc('bid_amount')->first();
        $currentHighest = $highestBid ? $highestBid->bid_amount : $batch->starting_price;

        // Validate bid amount
        $bidAmount = $request->bid_amount;
        if ($bidAmount <= $currentHighest) {
            return response()->json([
                'message' => 'Bid must be higher than the current highest bid',
                'current_highest' => $currentHighest
            ], 422);
        }

        // Check increment rule
        $incrementRule = $batch->getBidIncrementRuleArrayAttribute();
        if ($incrementRule['type'] === 'flat' && $bidAmount < $currentHighest + $incrementRule['step']) {
            return response()->json([
                'message' => 'Bid must be at least ' . ($currentHighest + $incrementRule['step']),
                'min_bid' => $currentHighest + $incrementRule['step']
            ], 422);
        }

        // Create bid
        $bid = new Bid([
            'batch_id' => $batch->id,
            'user_id' => auth()->id(),
            'bid_amount' => $bidAmount,
            'submitted_at' => now(),
            'status' => 'valid'
        ]);

        $bid->save();

        return response()->json([
            'message' => 'Bid placed successfully',
            'bid' => $bid
        ], 201);
    }
}
