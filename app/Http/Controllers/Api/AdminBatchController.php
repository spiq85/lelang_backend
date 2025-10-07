<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBatch;
use App\Models\BatchLot;
use Illuminate\Http\Request;

class AdminBatchController extends Controller
{
    // Seller submits batch (draft -> pending_review) via Seller UI (optional separate endpoint).
    // Admin "approve" -> we move it to published directly (per your brief).
    public function approve(Request $request, AuctionBatch $batch)
    {
        if (!in_array($batch->status, ['draft','pending_review'])) {
            return response()->json(['message' => 'Batch must be draft or pending_review to approve'], 422);
        }

        // must have at least 1 lot
        if (!$batch->lots()->exists()) {
            return response()->json(['message' => 'Batch must contain at least one lot'], 422);
        }

        // basic time checks
        if (!$batch->start_at || !$batch->end_at || $batch->end_at->lte($batch->start_at)) {
            return response()->json(['message' => 'Invalid start/end time'], 422);
        }

        $batch->update(['status' => 'published']);
        return response()->json(['message' => 'Batch approved & published', 'batch' => $batch->fresh('lots')]);
    }

    // Optional: keep "publish" as an explicit action (alias of approve).
    public function publish(Request $request, AuctionBatch $batch)
    {
        // allow publish only if ready
        if (!in_array($batch->status, ['draft','pending_review'])) {
            return response()->json(['message' => 'Batch not publishable from current status'], 422);
        }

        if (!$batch->lots()->exists()) {
            return response()->json(['message' => 'Batch must contain at least one lot'], 422);
        }

        $batch->update(['status' => 'published']);
        return response()->json(['message' => 'Batch published', 'batch' => $batch->fresh('lots')]);
    }

    public function close(Request $request, AuctionBatch $batch)
    {
        if ($batch->status !== 'published') {
            return response()->json(['message' => 'Only published batches can be closed'], 422);
        }

        // close all open lots (you may keep status if already awarded/settled)
        $batch->lots()->where('status','open')->update(['status' => 'closed']);

        $batch->update(['status' => 'closed']);
        return response()->json(['message' => 'Batch closed', 'batch' => $batch->fresh('lots')]);
    }
}
