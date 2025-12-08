<?php

namespace App\Console\Commands;

use App\Models\AuctionBatch;
use App\Models\BidSet;
use App\Notifications\AuctionBatchStatusNotification;
use App\Notifications\BatchEndingSoonNotification;
use Illuminate\Console\Command;

class BatchesTick extends Command
{
    protected $signature = 'batches:tick';
    protected $description = 'Transition batch status & send time-window notifications';

    public function handle(): int
    {
        $now = now('Asia/Jakarta');

        // 1) Publish otomatis saat sudah waktunya
        AuctionBatch::where('status','published')
            ->whereNotNull('start_at')
            ->where('start_at','<=',$now)
            ->get()->each(function($batch){
                // Tambahkan logika jika ingin auto-publish dari status lain
            });

        // 2) Batch ending soon (5 menit lagi)
        $endingSoon = AuctionBatch::where('status','published')
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

        // 3) Auto-close batch saat end_at lewat
        $toClose = AuctionBatch::where('status','published')
            ->whereNotNull('end_at')
            ->where('end_at','<=',$now)
            ->get();

        foreach ($toClose as $batch) {
            $batch->update(['status'=>'closed']);
            $batch->seller->notify(new AuctionBatchStatusNotification(
                'Closed',
                "Batch \"{$batch->title}\" telah berakhir.",
                $batch->id
            ));
        }

        $this->info('batches:tick done at ' . $now->toDateTimeString());
        return self::SUCCESS;
    }
}
