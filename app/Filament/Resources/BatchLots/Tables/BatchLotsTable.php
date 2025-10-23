<?php

namespace App\Filament\Resources\BatchLots\Tables;

use Filament\Actions;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class BatchLotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if ($user->role === 'seller') {
                    $query->whereHas('batch', fn ($q) => $q->where('seller_id', $user->id));
                }
            })
            ->columns([
                TextColumn::make('batch.title')->label('Batch Title')->sortable()->searchable(),
                TextColumn::make('product.product_name')->label('Product')->sortable()->searchable(),
                TextColumn::make('lot_number')->numeric()->sortable(),
                TextColumn::make('starting_price')->money('idr', true)->sortable(),
                TextColumn::make('reserve_price')->money('idr', true)->sortable(),
                TextColumn::make('status')->badge()->colors([
                    'gray' => 'open',
                    'info' => 'closed',
                    'warning' => 'awarded',
                    'success' => 'settled',
                ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
