<?php

namespace App\Notifications;

use App\Models\AuctionBatch;
use Illuminate\Notifications\Notification;

class BatchEndingSoonNotification extends Notification
{

    public function __construct(public AuctionBatch $batch)
    {
        $this->batch->loadMissing('lots');
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'batch_ending_soon',
            'batch_id' => $this->batch->id,
            'batch_title' => $this->batch->title,
            'ends_at' => optional($this->batch->end_at)->toIso8601String(),
            'message' => "Lelang '{$this->batch->title}' berakhir 5 menit lagi.",
        ];
    }
}
