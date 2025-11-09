<?php

namespace App\Notifications;

use App\Models\LotWinner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class LotWinnerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LotWinner $winner)
    {
        $this->winner = $winner->loadMissing(['lot.batch']);
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $lot = $this->winner->lot;
        $batch = $lot?->batch;

        return [
            'type' => 'lot_winner',
            'batch_id' => $batch?->id,
            'batch_title' => $batch?->title,
            'lot_id' => $lot?->id,
            'lot_number' => $lot?->lot_number,
            'amount' => $this->winner->winning_bid_amount,
            'deeplink' => $batch && $lot ? "/batches/{$batch->id}/lots/{$lot->id}" : null,
            'message' => $batch && $lot
                ? "Selamat! Kamu menang Lot #{$lot->lot_number} di '{$batch->title}'."
                : 'Selamat! Kamu memenangkan salah satu lot.',
        ];
    }
}
