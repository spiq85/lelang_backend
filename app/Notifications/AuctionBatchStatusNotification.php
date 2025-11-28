<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AuctionBatchStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $status,
        public string $message,
        public ?int $batchId = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'      => "Auction Batch {$this->status}",
            'body'       => $this->message,
            'batch_id'   => $this->batchId,
            'status'     => $this->status,
            'icon'       => $this->getIcon(),
            'iconColor'  => $this->getColor(),
        ];
    }

    private function getIcon(): string
    {
        return match (strtolower($this->status)) {
            'approved'        => 'heroicon-o-check-circle',
            'rejected'        => 'heroicon-o-x-circle',
            'pending_review'  => 'heroicon-o-paper-airplane',
            'draft'           => 'heroicon-o-document',
            default           => 'heroicon-o-bell',
        };
    }

    private function getColor(): string
    {
        return match (strtolower($this->status)) {
            'approved'        => 'success',
            'rejected'        => 'danger',
            'pending_review'  => 'warning',
            'draft'           => 'gray',
            default           => 'primary',
        };
    }
}
