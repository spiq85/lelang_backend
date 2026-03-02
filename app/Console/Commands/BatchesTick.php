<?php

namespace App\Console\Commands;

use App\Models\AuctionBatch;
use App\Models\BatchLot;
use App\Models\BidItem;
use App\Models\BidSet;
use App\Models\LotWinner;
use App\Notifications\AuctionBatchStatusNotification;
use App\Notifications\BatchEndingSoonNotification;
use App\Notifications\LotWinnerNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BatchesTick extends Command
{
    protected $signature = 'batches:tick';
    protected $description = 'Auto-close expired batches, determine winners, and send notifications';

    public function handle(): int
    {
        $now = now('Asia/Jakarta');

        // 1) Batch ending soon (5 menit lagi) - notify bidders
        $endingSoon = AuctionBatch::where('status', 'published')
            ->whereNotNull('end_at')
            ->whereBetween('end_at', [
                $now->copy()->addMinutes(5)->startOfMinute(),
                $now->copy()->addMinutes(5)->endOfMinute()
            ])
            ->get();

        foreach ($endingSoon as $batch) {
            $userIds = BidSet::where('batch_id', $batch->id)
                ->where('status', 'valid')
                ->pluck('user_id')
                ->unique();

            foreach ($userIds as $uid) {
                $user = \App\Models\User::find($uid);
                if ($user) {
                    $user->notify(new BatchEndingSoonNotification($batch));
                }
            }
        }

        // 2) Auto-close expired batches AND determine winners automatically
        $toClose = AuctionBatch::where('status', 'published')
            ->whereNotNull('end_at')
            ->where('end_at', '<=', $now)
            ->get();

        foreach ($toClose as $batch) {
            DB::transaction(function () use ($batch) {
                // Process each open lot in the batch
                $lots = $batch->lots()->where('status', 'open')->get();

                foreach ($lots as $lot) {
                    $this->determineWinner($lot, $batch);
                }

                // Close the batch
                $batch->update(['status' => 'closed']);

                // Notify seller
                $batch->seller->notify(new AuctionBatchStatusNotification(
                    'Closed',
                    "Batch \"{$batch->title}\" telah berakhir. Pemenang telah ditentukan otomatis.",
                    $batch->id
                ));
            });
        }

        $closedCount = $toClose->count();
        $this->info("batches:tick done at {$now->toDateTimeString()} — closed {$closedCount} batch(es)");
        return self::SUCCESS;
    }

    /**
     * Determine the winner for a lot based on highest bid.
     */
    private function determineWinner(BatchLot $lot, AuctionBatch $batch): void
    {
        // Find the highest bid for this lot across all products
        $highestBid = BidItem::where('lot_id', $lot->id)
            ->whereHas('bidSet', function ($q) use ($batch) {
                $q->where('batch_id', $batch->id)
                  ->where('status', 'valid');
            })
            ->orderByDesc('bid_amount')
            ->first();

        if (!$highestBid) {
            // No bids on this lot - just close it
            $lot->update(['status' => 'closed']);
            return;
        }

        // Get the winning user
        $winnerUserId = $highestBid->bidSet->user_id;

        // Create or update lot winner record
        $winner = LotWinner::updateOrCreate(
            ['lot_id' => $lot->id],
            [
                'winner_user_id' => $winnerUserId,
                'winning_bid_amount' => $highestBid->bid_amount,
                'choosen_by' => null, // System-determined (not admin)
                'reason' => 'Otomatis: Bid tertinggi saat waktu lelang berakhir',
                'decided_at' => now(),
            ]
        );

        // Update lot status to awarded
        $lot->update(['status' => 'awarded']);

        // Notify the winner
        $winnerUser = \App\Models\User::find($winnerUserId);
        if ($winnerUser) {
            $winnerUser->notify(new LotWinnerNotification($winner));
        }

        $this->info("  Lot #{$lot->lot_number}: Winner is user #{$winnerUserId} with bid {$highestBid->bid_amount}");
    }
}
