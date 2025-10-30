<?php

namespace App\Filament\Resources\AuctionBatches\Tables;

use App\Models\User;
use App\Notifications\AuctionBatchStatusNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuctionBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                if ($user->role === 'seller') {
                    $query->where('seller_id', $user->id);
                }
            })
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
                        'gray' => 'draft',
                        'warning' => 'pending_review',
                        'success' => 'published',
                        'danger' => 'cancelled',
                        'secondary' => 'closed',
                    ])
                    ->sortable(),

                TextColumn::make('start_at')->label('Start')->dateTime(),
                TextColumn::make('end_at')->label('End')->dateTime(),
                TextColumn::make('seller.full_name')->label('Seller')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),

                // Seller: Send for Review
                Action::make('send_review')
                    ->label('Send for Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn ($record) =>
                        auth()->user()->role === 'seller' && $record->status === 'draft'
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        if ($record->lots()->whereNotNull('product_id')->count() === 0) {
                            Notification::make()
                                ->title('Cannot Send for Review')
                                ->body('Please fill at least one product in Batch Lot before sending.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update(['status' => 'pending_review']);

                        // Notify Admins
                        User::role('super_admin')->each(function ($admin) use ($record) {
                            $admin->notify(new AuctionBatchStatusNotification(
                                'Pending Review',
                                "Seller {$record->seller->full_name} mengirim batch \"{$record->title}\" untuk direview.",
                                $record->id
                            ));
                        });

                        Notification::make()
                            ->title('Batch Sent for Review')
                            ->body("Batch \"{$record->title}\" has been sent to admin for approval.")
                            ->success()
                            ->send();
                    }),

                // Admin: Approve
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) =>
                        auth()->user()->role !== 'seller' && $record->status === 'pending_review'
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'published',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        $record->seller->notify(new AuctionBatchStatusNotification(
                            'Approved',
                            "Batch \"{$record->title}\" sudah disetujui oleh admin.",
                            $record->id,
                        ));

                        Notification::make()
                            ->title('Batch Approved')
                            ->body("Auction batch \"{$record->title}\" has been approved.")
                            ->success()
                            ->send();
                    }),

                // Admin: Reject
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) =>
                        auth()->user()->role !== 'seller' && $record->status === 'pending_review'
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'cancelled']);

                        $record->seller->notify(new AuctionBatchStatusNotification(
                            'Rejected',
                            "Batch \"{$record->title}\" ditolak oleh admin.",
                            $record->id,
                        ));

                        Notification::make()
                            ->title('Batch Rejected')
                            ->body("Auction batch \"{$record->title}\" was rejected.")
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
