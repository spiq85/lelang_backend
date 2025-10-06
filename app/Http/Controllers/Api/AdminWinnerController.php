<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LotWinner;
use App\Models\BatchLot;
use App\Models\BidItem;

class AdminWinnerController extends Controller
{
    public function select(Request $r, $lotId)
    {
        $lot = BatchLot::with('batch')->findOrFail($lotId);

        $data = $r->validate([
            'winner_user_id' => 'required|exists:users,id',
            'winning_bid_amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string'
        ]);

        // validasi: pemenang harus pernah bid lot ini
        $hasBid = BidItem::where('lot_id',$lotId)
            ->whereHas('bidSet', function($q) use ($data,$lot){
                $q->where('user_id',$data['winner_user_id'])
                  ->where('batch_id',$lot->batch_id)
                  ->where('status','valid');
            })
            ->where('bid_amount',$data['winning_bid_amount'])
            ->exists();

        if (!$hasBid) {
            return response()->json(['message'=>'Winner must have a matching bid on this lot'], 422);
        }

        $winner = LotWinner::updateOrCreate(
            ['lot_id'=>$lotId],
            [
                'winner_user_id'=>$data['winner_user_id'],
                'winning_bid_amount'=>$data['winning_bid_amount'],
                'choosen_by'=>$r->user()->id,
                'reason'=>$data['reason'] ?? null,
                'decided_at'=>now(),
            ]
        );

        $lot->update(['status'=>'awarded']);
        return response()->json(['message'=>'Winner selected','winner'=>$winner]);
    }
}
