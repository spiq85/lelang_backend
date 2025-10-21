<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AuctionBatchStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $status,
        public string $message,
        public ?int $batchId = null,
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => "Auction Batch {$this->status}",
            'message' => $this->message,
            'batch_id' => $this->batchId,
            'time' => now()->toDateTimeString(),
        ];
    }
}