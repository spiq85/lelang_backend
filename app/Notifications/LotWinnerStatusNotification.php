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
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Congratulations! You won a lot!',
            'message' => "You won lot #{$this->winner->lot->lot_number} in batch '{$this->winner->lot->batch->title}'.",
            'url' => route('filament.resources.batch-lots.edit', ['record' => $this->winner->lot_id]),
        ]);
    }
}
