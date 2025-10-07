<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BatchLot;
use App\Models\AuctionBatch;

class BatchLotController extends Controller
{
    public function index($batchId)
    {
        $lots = BatchLot::with('product.images')->where('batch_id', $batchId)
            ->orderBy('lot_number')->get();

        return response()->json($lots);
    }

    public function store(Request $request, $batchId)
    {
        $batch = AuctionBatch::findOrFail($batchId);

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'lot_number' => 'required|integer|min:1',
            'starting_price' => 'required|numeric|min:0',
            'reserve_price' => 'nullable|numeric|min:0',
        ]);

        $lot = $batch->lots()->create($data);

        return response()->json($lot->load('product.images'),201);
    }

    public function update(Request $request, $id)
    {
        $lot = BatchLot::findOrFail($id);

        $data = $request->validate([
            'lot_number' => 'sometimes|integer|min:1',
            'starting_price' => 'sometimes|numeric|min:0',
            'reserve_price' => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|in:open,closed,awarded,settled',
        ]);
        $lot->update($data);

        return response()->json($lot->load('product.images'));
    }

    public function destroy($id)
    {
        BatchLot::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Lot Deleted'
        ]);
    }
}
