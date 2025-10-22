<?php

namespace App\Notifications;

use Filament\Actions\Action as ActionsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class AuctionBatchStatusNotification extends Notification implements ShouldQueue
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
            'title' => "Auction Batch {$this->status}",
            'body' => $this->message,
            'batch_id' => $this->batchId,
            'status' => $this->status,
            'icon' => $this->getIcon(),
            'iconColor' => $this->getColor(),
            'actions' => $this->getActions(),
        ];
    }

    /**
     * Dapatkan Filament Notification untuk ditampilkan di UI
     */
    public function toFilament($notifiable): FilamentNotification
    {
        return FilamentNotification::make()
            ->title("Auction Batch {$this->status}")
            ->body($this->message)
            ->icon($this->getIcon())
            ->iconColor($this->getColor())
            ->actions($this->getActions())
            ->duration(5000);
    }

    private function getIcon(): string
    {
        return match(strtolower($this->status)) {
            'approved' => 'heroicon-o-check-circle',
            'rejected' => 'heroicon-o-x-circle',
            'started' => 'heroicon-o-play',
            'closed' => 'heroicon-o-lock-closed',
            default => 'heroicon-o-bell',
        };
    }

    private function getColor(): string
    {
        return match(strtolower($this->status)) {
            'approved' => 'success',
            'rejected' => 'danger',
            'started' => 'info',
            'closed' => 'gray',
            default => 'primary',
        };
    }

    private function getActions(): array
    {
        if (!$this->batchId) {
            return [];
        }

        return [
            ActionsAction::make('view')
                ->label('View Details')
                ->url(route('filament.admin.resources.auction-batches.view', ['record' => $this->batchId]))
                ->button(),
        ];
    }
}