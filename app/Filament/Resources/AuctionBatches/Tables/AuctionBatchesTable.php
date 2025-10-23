<?php

namespace App\Filament\Resources\AuctionBatches\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use App\Notifications\AuctionBatchStatusNotification;
use App\Models\User;

class AuctionBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if ($user->role === 'seller') {
                    $query->where('seller_id', $user->id);
                } else {
                    $query->whereNot('status', 'draft');
                }
            })
            ->columns([
                TextColumn::make('title')->label('Batch Title')->sortable()->searchable(),
                TextColumn::make('status')->badge()->colors([
                    'gray'     => 'draft',
                    'warning'  => 'pending_review',
                    'success'  => 'published',
                    'danger'   => 'cancelled',
                ]),
                TextColumn::make('start_at')->dateTime()->label('Start'),
                TextColumn::make('end_at')->dateTime()->label('End'),
                TextColumn::make('seller.full_name')->label('Seller'),
            ])
            ->actions([
                EditAction::make(),

                // ✅ Seller: Send for Review
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

                        // 🔔 Notify Admins
                        $admins = User::role('super_admin')->get();
                        foreach ($admins as $admin) {
                            $admin->notify(new AuctionBatchStatusNotification(
                                'Pending Review',
                                "Seller {$record->seller->full_name} mengirim batch \"{$record->title}\" untuk direview.",
                                $record->id
                            ));
                        }

                        Notification::make()
                            ->title('Batch Sent for Review')
                            ->body("Batch \"{$record->title}\" has been sent to admin for approval.")
                            ->success()
                            ->send();
                    }),

                // ✅ Admin: Approve
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        auth()->user()->role !== 'seller' && $record->status === 'pending_review'
                    )
                    ->action(function ($record) {
                        $record->update(['status' => 'published']);

                        // Notify seller
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

                // ✅ Admin: Reject
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        auth()->user()->role !== 'seller' && $record->status === 'pending_review'
                    )
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
