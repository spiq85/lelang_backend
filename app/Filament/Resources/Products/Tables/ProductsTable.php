<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductStatusNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification as FilamentNotification;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                // Seller hanya bisa lihat produknya sendiri
                if ($user->role === 'seller') {
                    $query->where('seller_id', $user->id);
                } else {
                    // Admin & super_admin bisa lihat semua produk
                    $query->whereNotNull('id');
                }
            })
            ->columns([
                TextColumn::make('seller.full_name')
                    ->label('Seller')
                    ->visible(fn() => in_array(auth()->user()->role, ['admin', 'super_admin']))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('product_name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('base_price')
                    ->label('Base Price')
                    ->money('IDR', locale: 'id_ID')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'published',
                        'gray' => 'draft',
                    ])
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                // === APPROVE ===
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn($record) =>
                        in_array(auth()->user()->role, ['admin', 'super_admin']) &&
                        in_array($record->status, ['pending', 'draft'])
                    )
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        $record->update(['status' => 'published']);

                        if ($record->seller) {
                            // === Laravel Notification (database)
                            $record->seller->notify(new ProductStatusNotification(
                                'Approved',
                                "Your product '{$record->product_name}' has been approved and published.",
                                $record->id
                            ));

                            // === Filament Notification (UI 🔔)
                            Notification::make()
                                ->title('Product Approved')
                                ->body("Your product '{$record->product_name}' has been approved.")
                                ->success()
                                ->icon('heroicon-o-check-circle')
                                ->sendToDatabase($record->seller);
                        }

                        // Notifikasi untuk admin yang melakukan approve
                        FilamentNotification::make()
                            ->title('Product Approved')
                            ->body("{$record->product_name} has been approved successfully.")
                            ->success()
                            ->send();
                    }),

                // === REJECT ===
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(
                        fn($record) =>
                        in_array(auth()->user()->role, ['admin', 'super_admin']) &&
                        in_array($record->status, ['pending', 'draft'])
                    )
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        $record->update(['status' => 'rejected']);

                        if ($record->seller) {
                            $record->seller->notify(new ProductStatusNotification(
                                'Rejected',
                                "Your product '{$record->product_name}' was rejected by admin.",
                                $record->id
                            ));

                            Notification::make()
                                ->title('Product Rejected')
                                ->body("Your product '{$record->product_name}' was rejected by admin.")
                                ->danger()
                                ->icon('heroicon-o-x-circle')
                                ->sendToDatabase($record->seller);
                        }

                        FilamentNotification::make()
                            ->title('Product Rejected')
                            ->body("{$record->product_name} has been rejected.")
                            ->danger()
                            ->send();
                    }),

                EditAction::make(),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
