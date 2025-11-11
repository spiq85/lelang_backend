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
        $batches = AuctionBatch::with(['lots.product.images', 'lots.product.categories'])
            ->when($request->status, fn($q,$s)=>$q->where('status',$s))
            ->latest()
            ->paginate($request->integer('per_page',15));

        // map tambahan countdown per item
        $batches->getCollection()->transform(function ($b) {
            return array_merge($b->toArray(), [
                'now' => now()->toIso8601String(),
                'phase' => $b->phase,
                'starts_in_seconds' => $b->starts_in_seconds,
                'ends_in_seconds' => $b->ends_in_seconds,
                'progress_percent' => $b->progress_percent,
            ]);
        });

        return response()->json($batches);
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
            $request,
            'seller_id' => $request ['seller_id'],
            'title' => $request ['title'],
            'description' => $request ['description'],
            'start_at' => $request ['start_at'],
            'end_at' => $request ['end_date'],
            'bid_increment_rule' => $request ['bid_increment_rule'],
            'reserve_rule' => $request ['reserve_rule'],
            'status' => $request ['status'],
            'created_by' => $request ['created_by'],
        ]);

        return response()->json(
            $batch->load(['lots.product.images', 'lots.product.categories']),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $batch = AuctionBatch::with(['lots.product.images', 'lots.product.categories', 'bidSets.user'])
            ->findOrFail($id);

        $lotSummaries = $batch->lots->map(function($lot){
            $highest = $lot->bidItems()
                ->join('bid_sets','bid_sets.id','=','bid_items.bid_set_id')
                ->where('bid_sets.status','valid')
                ->orderByDesc('bid_amount')
                ->orderBy('bid_sets.submitted_at')
                ->first();

            return [
                'lot_id' => $lot->id,
                'lot_number' => $lot->lot_number,
                'current_highest' => $highest?->bid_amount,
            ];
        });

        return response()->json([
            'batch' => array_merge($batch->toArray(), [
                'now' => now()->toIso8601String(),
                'phase' => $batch->phase,
                'starts_in_seconds' => $batch->starts_in_seconds,
                'ends_in_seconds' => $batch->ends_in_seconds,
                'progress_percent' => $batch->progress_percent,
            ]),
            'lots' => $batch->lots,
            'summaries' => $lotSummaries,
        ]);
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
                'lots.product.images', 'lots.product.categories'
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
