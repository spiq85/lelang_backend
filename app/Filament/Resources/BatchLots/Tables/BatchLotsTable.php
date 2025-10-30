<?php

namespace App\Filament\Resources\BatchLots\Tables;

use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class BatchLotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if ($user->role === 'seller') {
                    $query->whereHas('batch', fn($q) => $q->where('seller_id', $user->id));
                }
            })
            ->columns([
                TextColumn::make('batch.title')
                    ->label('Batch Title')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('product.product_name')
                    ->label('Product')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('lot_number')
                    ->label('Lot #')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('starting_price')
                    ->label('Starting Price')
                    ->money('idr', true)
                    ->sortable(),

                TextColumn::make('reserve_price')
                    ->label('Reserve Price')
                    ->money('idr', true)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray'    => 'open',
                        'info'    => 'closed',
                        'warning' => 'awarded',
                        'success' => 'settled',
                    ])
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),
            ])
            ->actions([
                EditAction::make(),

                // 🔹 View Bidders
                Action::make('view_bidders')
                    ->label('View Bidders')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(
                        fn($record) =>
                        auth()->user()->role !== 'seller' && $record->bidItems()->exists()
                    )
                    ->modalHeading('Bidders for this Lot')
                    ->modalContent(function ($record) {
                        $bids = $record->bidItems()
                            ->with(['bidSet.user'])
                            ->orderByDesc('bid_amount')
                            ->get();

                        if ($bids->isEmpty()) {
                            return 'No bids placed for this lot.';
                        }

                        $html = '<div class="space-y-2">';
                        foreach ($bids as $bid) {
                            $html .= sprintf(
                                '<div class="flex justify-between border-b pb-1">
                        <span>%s</span>
                        <span>Rp %s</span>
                    </div>',
                                e($bid->bidSet->user->full_name),
                                number_format($bid->bid_amount, 0, ',', '.')
                            );
                        }
                        $html .= '</div>';

                        return new \Illuminate\Support\HtmlString($html);
                    }),

                // 🔹 Select Winner (action terpisah)
                LotWinnerAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
