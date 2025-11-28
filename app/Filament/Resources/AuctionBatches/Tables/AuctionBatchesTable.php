<?php

namespace App\Filament\Resources\AuctionBatches\Tables;

use App\Models\User;
use App\Notifications\AuctionBatchStatusNotification;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Builder;

class AuctionBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->when(auth()->user()->role === 'seller',
                    fn ($q) => $q->where('seller_id', auth()->id())
                )
            )

            ->columns([
                TextColumn::make('title')
                    ->label('Batch Title')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'gray'      => 'draft',
                        'warning'   => 'pending_review',
                        'success'   => 'published',
                        'danger'    => 'cancelled',
                        'secondary' => 'closed',
                    ])
                    ->sortable(),

                TextColumn::make('start_at')->label('Start')->dateTime('d M Y H:i'),
                TextColumn::make('end_at')->label('End')->dateTime('d M Y H:i'),

                TextColumn::make('seller.full_name')
                    ->label('Seller')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->actions([

                EditAction::make(),

                /*
                 |--------------------------------------------------------------------------
                 | SEND FOR REVIEW — Seller → Admin
                 |--------------------------------------------------------------------------
                */
                Action::make('send_review')
                    ->label('Send for Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn ($record) =>
                        auth()->user()->role === 'seller'
                        && $record->status === 'draft'
                    )
                    ->requiresConfirmation()
                    ->disabled(fn ($record) =>
                        $record->loadCount('batchLotProducts')->batch_lot_products_count === 0
                    )
                    ->action(function ($record) {

                        $record->update(['status' => 'pending_review']);

                        $admins = User::role('super_admin')->get();

                        foreach ($admins as $admin) {

                            // DB notification
                            $admin->notify(new AuctionBatchStatusNotification(
                                'Pending Review',
                                "Seller {$record->seller->full_name} mengirim batch \"{$record->title}\" untuk direview.",
                                $record->id
                            ));

                            // Filament bell notification
                            FilamentNotification::make()
                                ->title('Batch Pending Review')
                                ->body("Batch \"{$record->title}\" dikirim untuk review.")
                                ->warning()
                                ->sendToDatabase($admin);
                        }

                        FilamentNotification::make()
                            ->title('Dikirim untuk Review')
                            ->body("Batch \"{$record->title}\" berhasil dikirim.")
                            ->success()
                            ->send();
                    }),

                /*
                 |--------------------------------------------------------------------------
                 | APPROVE — Admin → Seller
                 |--------------------------------------------------------------------------
                */
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) =>
                        auth()->user()->role !== 'seller'
                        && $record->status === 'pending_review'
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {

                        $record->update([
                            'status'      => 'published',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        // DB notification for seller
                        $record->seller->notify(new AuctionBatchStatusNotification(
                            'Approved',
                            "Batch \"{$record->title}\" telah disetujui oleh admin.",
                            $record->id
                        ));

                        // Filament bell notification
                        FilamentNotification::make()
                            ->title('Batch Approved')
                            ->body("Batch \"{$record->title}\" telah disetujui.")
                            ->success()
                            ->sendToDatabase($record->seller);

                        FilamentNotification::make()
                            ->title('Success!')
                            ->body("Auction batch approved.")
                            ->success()
                            ->send();
                    }),

                /*
                 |--------------------------------------------------------------------------
                 | REJECT — Admin → Seller
                 |--------------------------------------------------------------------------
                */
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) =>
                        auth()->user()->role !== 'seller'
                        && $record->status === 'pending_review'
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {

                        $record->update(['status' => 'cancelled']);

                        // DB notification
                        $record->seller->notify(new AuctionBatchStatusNotification(
                            'Rejected',
                            "Batch \"{$record->title}\" ditolak oleh admin.",
                            $record->id
                        ));

                        // Filament bell
                        FilamentNotification::make()
                            ->title('Batch Ditolak')
                            ->body("Batch \"{$record->title}\" telah ditolak admin.")
                            ->danger()
                            ->sendToDatabase($record->seller);

                        FilamentNotification::make()
                            ->title('Ditolak')
                            ->body("Auction batch rejected.")
                            ->danger()
                            ->send();
                    }),
            ])

            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
