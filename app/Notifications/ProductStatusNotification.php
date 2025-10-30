<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $status,
        public string $message,
        public ?int $productId = null,
        public ?string $senderRole = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database']; // Simpan ke tabel notifications
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => "Product {$this->status}",
            'body' => $this->message,
            'product_id' => $this->productId,
            'status' => $this->status,
            'icon' => $this->getIcon(),
            'iconColor' => $this->getColor(),
        ];
    }

    private function getIcon(): string
    {
        return match (strtolower($this->status)) {
            'approved', 'published' => 'heroicon-o-check-circle',
            'rejected' => 'heroicon-o-x-circle',
            'pending', 'pending_review' => 'heroicon-o-paper-airplane',
            'draft' => 'heroicon-o-document',
            default => 'heroicon-o-bell',
        };
    }

    private function getColor(): string
    {
        return match (strtolower($this->status)) {
            'approved', 'published' => 'success',
            'rejected' => 'danger',
            'pending', 'pending_review' => 'warning',
            'draft' => 'gray',
            default => 'primary',
        };
    }
}
