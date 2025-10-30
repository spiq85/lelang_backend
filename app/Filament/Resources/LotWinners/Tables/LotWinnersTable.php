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
                TextColumn::make('lot_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('winner_user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('winning_bid_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('choosen_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('decided_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
