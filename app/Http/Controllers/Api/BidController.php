<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\AuctionBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BidController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $bids = Bid::with(['batch.product', 'batch.seller', 'user'])
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->batch_id, function($query, $batchId) {
                return $query->where('batch_id', $batchId);
            })
            ->when($request->user_id, function($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($bids);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|exists:auction_batches,id',
            'user_id' => 'required|exists:users,id',
            'bid_amount' => 'required|numeric|min:0',
            'status' => 'required|in:valid,rejected,withdrawn'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if batch exists and is active
        $batch = AuctionBatch::find($request->batch_id);
        if (!$batch) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        // Create bid
        try {
            $bid = Bid::create([
                'batch_id' => $request->batch_id,
                'user_id' => $request->user_id,
                'bid_amount' => $request->bid_amount,
                'submitted_at' => now(),
                'status' => $request->status
            ]);

            return response()->json($bid->load(['batch.product', 'batch.seller', 'user']), 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create bid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $bid = Bid::with(['batch.product', 'batch.seller', 'user'])->findOrFail($id);
        return response()->json($bid);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $bid = Bid::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'batch_id' => 'sometimes|required|exists:auction_batches,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'bid_amount' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:valid,rejected,withdrawn'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $bid->update($request->all());
            return response()->json($bid->load(['batch.product', 'batch.seller', 'user']));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update bid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $bid = Bid::findOrFail($id);

        try {
            $bid->delete();
            return response()->json(['message' => 'Bid deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete bid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get highest bid for a batch
     */
    public function highestBid($batchId)
    {
        $highestBid = Bid::where('batch_id', $batchId)
            ->isValid() // Gunakan scope yang baru
            ->orderByDesc('bid_amount')
            ->orderBy('submitted_at')
            ->first();

        return response()->json($highestBid);
    }

    /**
     * Update bid status to winner
     */
    public function markAsWinner(Request $request, $id)
    {
        $bid = Bid::findOrFail($id);

        try {
            $bid->status = 'winner';
            $bid->save();

            return response()->json([
                'message' => 'Bid marked as winner',
                'bid' => $bid
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark bid as winner',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
