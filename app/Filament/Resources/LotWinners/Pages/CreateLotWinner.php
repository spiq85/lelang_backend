<?php

namespace App\Filament\Resources\LotWinners\Pages;

use App\Filament\Resources\LotWinners\LotWinnerResource;
use App\Models\BidItem;
use App\Notifications\LotWinnerNotification;
use Filament\Resources\Pages\CreateRecord;

class CreateLotWinner extends CreateRecord
{
    protected static string $resource = LotWinnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-fill winning_bid_amount dari bid tertinggi user di lot tsb
        $data['winning_bid_amount'] = BidItem::whereHas('bidSet', fn ($q) =>
            $q->where('user_id', $data['winner_user_id'])
        )->where('lot_id', $data['lot_id'])->max('bid_amount') ?? 0;

        // Pastikan choosen_by terisi admin saat ini
        $data['choosen_by'] = $data['choosen_by'] ?? auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $winner = $this->record->loadMissing(['lot', 'winner']);

        // Update status lot ke awarded
        if ($winner->lot) {
            $winner->lot->update(['status' => 'awarded']);
        }

        // Kirim notifikasi ke pemenang
        if ($winner->winner) {
            $winner->winner->notify(new LotWinnerNotification($winner));
        }
    }
}
