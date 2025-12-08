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
        $now = now();

        // 1) Publish otomatis saat sudah waktunya (opsional: dari pending_review -> published)
        AuctionBatch::where('status','published')
            ->whereNotNull('start_at')
            ->where('start_at','<=',$now)
            ->where(function($q){
                // kalau kamu ingin auto publish dari pending_review ke published:
                // $q->orWhere('status','pending_review');
            })
            ->get()->each(function($batch){
                // nothing to change here if already 'published'
                // tempat yang ini kalau mau auto-toggle dari pending_review, lakukan $batch->update(['status'=>'published'])
            });

        // 2) Batch ending soon (5 menit lagi)
        $endingSoon = AuctionBatch::where('status','published')
            ->whereNotNull('end_at')
            ->whereBetween('end_at', [now()->addMinutes(5)->startOfMinute(), now()->addMinutes(5)->endOfMinute()])
            ->get();

        foreach ($endingSoon as $batch) {
            // ambil user yang pernah submit bid set di batch ini
            $userIds = BidSet::where('batch_id',$batch->id)
                ->where('status','valid')
                ->pluck('user_id')->unique();

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
            // Notif seller bahwa batch closed
            $batch->seller->notify(new AuctionBatchStatusNotification(
                'Closed',
                "Batch \"{$batch->title}\" telah berakhir.",
                $batch->id
            ));
        }

        $this->info('batches:tick done at '.$now);
        return self::SUCCESS;
    }
}
