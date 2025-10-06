<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBatch;
use App\Models\BidSet;
use App\Models\BidItem;
use Illuminate\Http\Request;

class BidSetController extends Controller
{
    public function submit(Request $request, $batchId)
    {
        $batch = AuctionBatch::with('lots')->findOrFail($batchId);

        // window & status
        if ($batch->status !== 'published' || now()->lt($batch->start_at) || now()->gt($batch->end_at)) {
            return response()->json(['message' => 'Batch not accepting bids'], 400);
        }

        $payload = $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.lot_id'      => 'required|integer|exists:batch_lots,id',
            'items.*.bid_amount'  => 'required|numeric|min:0',
        ]);
        $items = $payload['items'];

        // must bid ALL open lots in this batch
        $openLots = $batch->lots()->where('status','open')->pluck('id')->toArray();
        $incomingLotIds = collect($items)->pluck('lot_id')->unique()->values()->toArray();

        sort($openLots);
        sort($incomingLotIds);
        if ($incomingLotIds !== $openLots) {
            return response()->json(['message' => 'You must bid on ALL open lots in this batch'], 422);
        }

        // per-lot increment check
        foreach ($items as $it) {
            $lot = $batch->lots->firstWhere('id', $it['lot_id']);
            $highest = BidItem::where('lot_id', $lot->id)
                ->join('bid_sets','bid_sets.id','=','bid_items.bid_set_id')
                ->where('bid_sets.status','valid')
                ->max('bid_amount');

            $baseline = $highest ?? $lot->starting_price;
            $minInc = $this->minIncrement($baseline, $batch->bid_increment_rule ?? []);

            if ($it['bid_amount'] < $baseline + $minInc) {
                return response()->json([
                    'message' => "Lot #{$lot->lot_number}: min bid " . ($baseline + $minInc),
                ], 422);
            }
        }

        // store bid set
        $bidSet = BidSet::create([
            'batch_id'     => $batch->id,
            'user_id'      => $request->user()->id,
            'submitted_at' => now(),
            'status'       => 'valid',
        ]);

        foreach ($items as $it) {
            BidItem::create([
                'bid_set_id' => $bidSet->id,
                'lot_id'     => $it['lot_id'],
                'bid_amount' => $it['bid_amount'],
            ]);
        }

        return response()->json(['message' => 'Bid set submitted', 'bid_set_id' => $bidSet->id], 201);
    }

    private function minIncrement(float $current, array $rule): float
    {
        $type = $rule['type'] ?? 'flat';
        if ($type === 'flat') return (float)($rule['step'] ?? 0);
        if ($type === 'percent') return round($current * (($rule['value'] ?? 0) / 100), 2);
        if ($type === 'tiered' && !empty($rule['steps'])) {
            foreach ($rule['steps'] as $s) {
                if (isset($s['lt']) && $current < $s['lt'])  return (float)$s['step'];
                if (isset($s['lte']) && $current <= $s['lte']) return (float)$s['step'];
            }
            return (float)($rule['steps'][array_key_last($rule['steps'])]['step'] ?? 0);
        }
        return 0.0;
    }
}
