<?php

namespace App\Filament\Resources\BatchLots\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Filament\Resources\BatchLots\Tables\LotWinnerAction;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BatchLotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lot_number')
                    ->label('Lot #')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('batch.title')
                    ->label('Batch')
                    ->searchable(),

                TextColumn::make('lotProducts_count')
                    ->label('Jumlah Produk')
                    ->getStateUsing(fn($record) => $record->lotProducts->count())
                    ->badge()
                    ->color(fn($state) => $state > 1 ? 'primary' : 'gray')
                    ->icon('heroicon-o-cube')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('first_price')
                    ->label('Starting Price')
                    ->getStateUsing(fn($record) => $record->lotProducts->first()?->starting_price ?? 0)
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open'     => 'success',
                        'closed'   => 'danger',
                        'awarded'  => 'warning',
                        'settled'  => 'info',
                        default    => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                LotWinnerAction::make(),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
