<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Notifications\ProductStatusNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
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
                if ($user?->role === 'seller') {
                    $query->where('seller_id', $user->id);
                }
                // Admin & super_admin bisa lihat semua
            })
            ->columns([
                TextColumn::make('seller.full_name')
                    ->label('Seller')
                    ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'super_admin']))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('base_price')
                    ->label('Harga Awal')
                    ->money('IDR', locale: 'id_ID')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning'  => 'pending',
                        'gray'     => 'draft',
                        'success'  => 'published',
                        'danger'   => 'rejected',
                        'info'     => 'sold',
                    ])
                    ->icons([
                        'heroicon-o-clock'         => 'pending',
                        'heroicon-o-pencil'        => 'draft',
                        'heroicon-o-check-circle'  => 'published',
                        'heroicon-o-x-circle'      => 'rejected',
                        'heroicon-o-shopping-bag'  => 'sold',
                    ])
                    ->sortable(),

                // URUTAN TRENDING – AMAN DARI NULL
                TextColumn::make('trending_order')
                    ->label('Urutan Trending')
                    ->badge()
                    ->color('success')
                    ->visible(fn ($record) => $record?->is_trending === true)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // TOGGLE TRENDING – HANYA ADMIN + AMAN DARI NULL
                ToggleColumn::make('is_trending')
                    ->label('Trending Product')
                    ->visible(fn () => auth()->user()?->role === 'admin')
                    ->disabled(fn () => auth()->user()?->role !== 'admin')
                    ->onColor('success')
                    ->offColor('gray')
                    ->onIcon('heroicon-o-fire')
                    ->offIcon('heroicon-o-fire')
                    ->afterStateUpdated(function (bool $state, ?Product $record) {
                        if (!$record) return;

                        if ($state) {
                            Notification::make()
                                ->title('Sukses!')
                                ->body("{$record->product_name} sekarang jadi #1 di Trending!")
                                ->success()
                                ->icon('heroicon-o-fire')
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Dihapus dari Trending')
                                ->body("{$record->product_name} sudah tidak trending lagi.")
                                ->info()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                // APPROVE
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Product $record) => 
                        in_array(auth()->user()?->role, ['admin', 'super_admin']) &&
                        in_array($record->status, ['pending', 'draft'])
                    )
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        $record->update(['status' => 'published']);

                        if ($record->seller) {
                            $record->seller->notify(new ProductStatusNotification(
                                'Approved',
                                "Produk '{$record->product_name}' telah disetujui dan dipublikasikan!",
                                $record->id
                            ));

                            Notification::make()
                                ->title('Produk Disetujui')
                                ->body("{$record->product_name} telah dipublikasikan.")
                                ->success()
                                ->sendToDatabase($record->seller);
                        }

                        FilamentNotification::make()
                            ->title('Berhasil!')
                            ->body("{$record->product_name} telah dipublikasikan.")
                            ->success()
                            ->send();
                    }),

                // REJECT
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Product $record) => 
                        in_array(auth()->user()?->role, ['admin', 'super_admin']) &&
                        in_array($record->status, ['pending', 'draft'])
                    )
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        $record->update(['status' => 'rejected']);

                        if ($record->seller) {
                            $record->seller->notify(new ProductStatusNotification(
                                'Rejected',
                                "Maaf, produk '{$record->product_name}' ditolak oleh admin.",
                                $record->id
                            ));

                            Notification::make()
                                ->title('Produk Ditolak')
                                ->body("{$record->product_name} ditolak oleh admin.")
                            ->danger()
                                ->sendToDatabase($record->seller);
                        }

                        FilamentNotification::make()
                            ->title('Ditolak')
                            ->body("{$record->product_name} telah ditolak.")
                            ->danger()
                            ->send();
                    }),

                EditAction::make(),
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}