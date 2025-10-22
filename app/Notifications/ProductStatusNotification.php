<?php

namespace App\Notifications;

use Filament\Actions\Action as ActionsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class ProductStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $status,
        public string $message,
        public ?int $productId = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
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
            'actions' => $this->getActions(),
        ];
    }

    public function toFilament($notifiable): FilamentNotification
    {
        return FilamentNotification::make()
            ->title("Product {$this->status}")
            ->body($this->message)
            ->icon($this->getIcon())
            ->iconColor($this->getColor())
            ->actions($this->getActions())
            ->duration(5000);
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

    private function getActions(): array
    {
        if (!$this->productId) {
            return [];
        }

        return [
            ActionsAction::make('view')
                ->label('View Product')
                ->url(route('filament.admin.resources.products.view', ['record' => $this->productId]))
                ->button(),
        ];
    }
}
