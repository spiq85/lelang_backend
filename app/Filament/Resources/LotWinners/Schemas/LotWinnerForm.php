<?php

namespace App\Filament\Resources\LotWinners\Schemas;

use App\Models\AuctionBatch;
use App\Models\BatchLot;
use App\Models\BidItem;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LotWinnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Step 1: Pilih Batch
                Select::make('batch_id')
                    ->label('Batch Lelang')
                    ->options(
                        AuctionBatch::query()
                            ->pluck('title', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('lot_id', null))
                    ->dehydrated(false)
                    ->hiddenOn('edit'),

                // Step 2: Pilih Lot dari Batch
                Select::make('lot_id')
                    ->label('Lot')
                    ->options(function (Get $get) {
                        $batchId = $get('batch_id');
                        if (!$batchId) return [];

                        return BatchLot::where('batch_id', $batchId)
                            ->with('lotProducts.product')
                            ->get()
                            ->mapWithKeys(function ($lot) {
                                $productNames = $lot->lotProducts
                                    ->map(fn ($lp) => $lp->product?->product_name ?? '-')
                                    ->join(', ');
                                return [$lot->id => "Lot #{$lot->lot_number} — {$productNames}"];
                            });
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('winner_user_id', null))
                    ->helperText('Pilih lot yang akan ditentukan pemenangnya')
                    ->hiddenOn('edit'),

                // Step 3: Pilih Pemenang dari bidders
                Select::make('winner_user_id')
                    ->label('Pemenang')
                    ->options(function (Get $get) {
                        $lotId = $get('lot_id');
                        if (!$lotId) return [];

                        return BidItem::query()
                            ->where('lot_id', $lotId)
                            ->join('bid_sets', 'bid_sets.id', '=', 'bid_items.bid_set_id')
                            ->join('users', 'users.id', '=', 'bid_sets.user_id')
                            ->select('users.id', 'users.full_name', 'bid_items.bid_amount')
                            ->orderByDesc('bid_items.bid_amount')
                            ->get()
                            ->mapWithKeys(fn ($b) => [
                                $b->id => "{$b->full_name} — Rp " . number_format($b->bid_amount, 0, ',', '.')
                            ]);
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, Get $get) {
                        if (!$state) {
                            $set('winning_bid_amount', null);
                            return;
                        }
                        $lotId = $get('lot_id');
                        $amount = BidItem::whereHas('bidSet', fn ($q) => $q->where('user_id', $state))
                            ->where('lot_id', $lotId)
                            ->max('bid_amount');
                        $set('winning_bid_amount', $amount ?? 0);
                    })
                    ->helperText('Bidder ditampilkan dengan nominal bid tertinggi'),

                // Winning bid auto-calculated (hidden, filled on create)
                TextInput::make('winning_bid_amount')
                    ->label('Nominal Bid Pemenang')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->readOnly()
                    ->helperText('Otomatis terisi dari bid tertinggi pemenang'),

                // Chosen by = current admin (otomatis)
                Placeholder::make('chosen_by_label')
                    ->label('Ditentukan Oleh')
                    ->content(fn () => auth()->user()?->full_name ?? '-')
                    ->hiddenOn('edit'),

                Hidden::make('choosen_by')
                    ->default(fn () => auth()->id()),

                Textarea::make('reason')
                    ->label('Alasan / Catatan')
                    ->rows(3)
                    ->placeholder('Opsional: alasan pemilihan pemenang')
                    ->columnSpanFull(),

                DateTimePicker::make('decided_at')
                    ->label('Waktu Keputusan')
                    ->default(now())
                    ->required(),
            ]);
    }
}
