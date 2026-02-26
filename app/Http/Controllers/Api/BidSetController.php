<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBatch;
use App\Models\BidSet;
use App\Models\BidItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BidSetController extends Controller
{
    /**
     * Submit bid untuk satu lot
     */
    public function submitPerLot(Request $request, $batchId, $lotId)
    {
        $batch = AuctionBatch::with('lots')->findOrFail($batchId);
        $lot = $batch->lots->firstWhere('id', $lotId);

        if (!$lot) {
            return response()->json(['message' => 'Lot tidak ditemukan'], 404);
        }

        // Cek status batch & waktu
        if ($batch->status !== 'published' || now()->lt($batch->start_at) || now()->gt($batch->end_at)) {
            return response()->json(['message' => 'Batch tidak menerima bid saat ini'], 400);
        }

        // Validasi input
        $payload = $request->validate([
            'bid_amount' => 'required|numeric|min:1',
            'product_id' => 'required|exists:products,id',
        ]);

        $bidAmount = (float) $payload['bid_amount'];
        $productId = (int) $payload['product_id'];

        $lotProduct = $lot->lotProducts()->where('product_id', $productId)->first();
        if (!$lotProduct) {
            return response()->json(['message' => 'Produk tidak ada di lot ini'], 422);
        }

        // Ambil bid tertinggi sebelumnya
        $highest = BidItem::where('lot_id', $lot->id)
            ->where('product_id', $productId)
            ->join('bid_sets', 'bid_sets.id', '=', 'bid_items.bid_set_id')
            ->where('bid_sets.status', 'valid')
            ->max('bid_amount');

        $baseline = $highest ?? (float) ($lotProduct->starting_price ?? $lot->starting_price);
        $minInc = 1; // minimal kenaikan bisa disesuaikan

        if ($bidAmount < $baseline + $minInc) {
            return response()->json([
                'message' => "Bid minimal adalah " . number_format($baseline + $minInc, 0, ',', '.'),
            ], 422);
        }

        // Buat BidSet untuk user
        $bidSet = BidSet::create([
            'batch_id'     => $batch->id,
            'user_id'      => Auth::id(),
            'submitted_at' => now(),
            'status'       => 'valid',
        ]);

        // Simpan BidItem
        $bidItem = BidItem::create([
            'bid_set_id' => $bidSet->id,
            'lot_id'     => $lot->id,
            'product_id' => $productId,
            'bid_amount' => $bidAmount,
        ]);

        return response()->json([
            'message' => 'Bid berhasil dikirim',
            'bid_set_id' => $bidSet->id,
            'data' => $bidItem,
        ], 201);
    }

    /**
     * Submit bid untuk seluruh batch (opsional)
     */
    public function submit(Request $request, $batchId)
    {
        // Placeholder untuk bid seluruh batch (jika diperlukan)
        return response()->json([
            'message' => 'Endpoint submit-bid-set belum diimplementasikan penuh'
        ], 200);
    }
}
