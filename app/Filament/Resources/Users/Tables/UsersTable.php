<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('npwp')
                    ->searchable(),
                TextColumn::make('phone_number')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'seller' => 'warning',
                        'bidder' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
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
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'seller' => 'Seller',
                        'bidder' => 'Bidder',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->role === 'seller' && !$record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Approve Seller')
                    ->modalDescription(fn ($record) => "Yakin ingin mengaktifkan akun seller \"{$record->full_name}\"?")
                    ->action(function ($record) {
                        $record->update(['is_active' => true]);
                        Notification::make()
                            ->title('Seller Approved')
                            ->body("Akun seller \"{$record->full_name}\" telah diaktifkan.")
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->role === 'seller' && !$record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Reject Seller')
                    ->modalDescription(fn ($record) => "Yakin ingin menolak akun seller \"{$record->full_name}\"? Akun akan dihapus.")
                    ->action(function ($record) {
                        $name = $record->full_name;
                        $record->delete();
                        Notification::make()
                            ->title('Seller Rejected')
                            ->body("Akun seller \"{$name}\" telah ditolak dan dihapus.")
                            ->warning()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
