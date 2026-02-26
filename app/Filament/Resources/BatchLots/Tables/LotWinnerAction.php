<?php

namespace App\Filament\Resources\BatchLots\Tables;

use App\Models\BatchLot;
use App\Models\BidItem;
use App\Models\LotWinner;
use App\Notifications\LotWinnerNotification;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class LotWinnerAction
{
    public static function make(): Action
    {
        return Action::make('select_winner')
            ->label('Select Winner')
            ->icon('heroicon-o-trophy')
            ->color('success')
            ->visible(fn ($record) =>
                auth()->user()->role !== 'seller' && $record->status === 'closed'
            )
            ->form(function (BatchLot $record) {
                $bidders = BidItem::query()
                    ->where('lot_id', $record->id)
                    ->join('bid_sets', 'bid_sets.id', '=', 'bid_items.bid_set_id')
                    ->join('users', 'users.id', '=', 'bid_sets.user_id')
                    ->select('users.id', 'users.full_name', 'bid_items.bid_amount')
                    ->orderByDesc('bid_items.bid_amount')
                    ->get()
                    ->mapWithKeys(fn ($b) => [
                        $b->id => "{$b->full_name} - Rp " . number_format($b->bid_amount, 0, ',', '.')
                    ]);

                return [
                    Forms\Components\Select::make('winner_user_id')
                        ->label('Choose Winner')
                        ->options($bidders)
                        ->required(),
                    Forms\Components\Textarea::make('reason')
                        ->label('Decision Note')
                        ->rows(2)
                        ->placeholder('Optional: reason for selecting this winner'),
                ];
            })
            ->action(function (BatchLot $record, array $data) {
                DB::transaction(function () use ($record, $data) {
                    // Simpan data pemenang
                    $winner = LotWinner::create([
                        'lot_id'             => $record->id,
                        'winner_user_id'     => $data['winner_user_id'],
                        'winning_bid_amount' => BidItem::whereHas('bidSet', fn ($q) =>
                            $q->where('user_id', $data['winner_user_id'])
                        )->where('lot_id', $record->id)->max('bid_amount'),
                        'choosen_by'         => auth()->id(),
                        'reason'             => $data['reason'] ?? null,
                        'decided_at'         => now(),
                    ]);

                    // Update status lot
                    $record->update(['status' => 'awarded']);

                    // Kirim notifikasi ke pemenang
                    $winner->winner->notify(new LotWinnerNotification($winner));
                });

                Notification::make()
                    ->title('Winner Selected')
                    ->body('Winner has been selected and notified successfully.')
                    ->success()
                    ->send();
            });
    }
}
