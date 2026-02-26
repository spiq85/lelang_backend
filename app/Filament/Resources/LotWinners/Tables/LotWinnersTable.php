<?php

namespace App\Filament\Resources\LotWinners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LotWinnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lot.batch.title')
                    ->label('Batch')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lot.lot_number')
                    ->label('Lot #')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('winner.full_name')
                    ->label('Pemenang')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-trophy')
                    ->color('success'),
                TextColumn::make('winning_bid_amount')
                    ->label('Nominal Bid')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),
                TextColumn::make('chooser.full_name')
                    ->label('Ditentukan Oleh')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('decided_at')
                    ->label('Waktu Keputusan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
