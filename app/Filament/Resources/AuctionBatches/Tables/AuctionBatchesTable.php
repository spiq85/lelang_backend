<?php

namespace App\Filament\Resources\AuctionBatches\Tables;

use Filament\Actions\Action as ActionsAction;
use Filament\Actions\BulkAction as ActionsBulkAction;
use Filament\Actions\BulkActionGroup as ActionsBulkActionGroup;
use Filament\Actions\DeleteBulkAction as ActionsDeleteBulkAction;
use Filament\Actions\EditAction as ActionsEditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
                TextColumn::make('seller.full_name')
                    ->label('Seller')
                    ->visible(fn() => in_array(auth()->user()->role, ['seller', 'super_admin']))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('product_id')
                    ->label('Product id')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('starting_price')
                    ->label('Starting price')
                    ->money('IDR', locale: 'id_ID')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('start_at')
                    ->label('Start Date')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color(fn($record) => $record->start_at > now() ? 'warning' : 'success'),

                TextColumn::make('end_at')
                    ->label('End Date')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color(fn($record) => $record->end_at < now() ? 'danger' : 'info'),

                TextColumn::make('reserve_price')
                    ->label('Reserve price')
                    ->money('IDR', locale: 'id_ID')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_review' => 'warning',
                        'published' => 'success',
                        'cancelled' => 'danger',
                        'closed' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => \Illuminate\Support\Str::headline($state ?? 'draft'))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('created_by')
                    ->label('Creator')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('approved_by')
                    ->label('Approved By')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('status')
                    ->options([
                        'draft'          => 'Draft',
                        'pending_review' => 'Pending Review',
                        'published'      => 'Published',
                        'cancelled'      => 'Cancelled',
                        'closed'         => 'Closed',
                    ]),
            ])
            ->recordActions([
                ActionsEditAction::make(),

                ActionsAction::make('sendToReview')
                    ->label('Send to Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn($record) =>
                        in_array($record->status, ['draft', 'cancelled']) &&
                        Auth::user()->can('send_to_review_auction_batch')
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'pending_review']);
                        Notification::make()
                            ->title('Batch sent to review.')
                            ->warning()
                            ->send();
                    }),

                ActionsAction::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) =>
                        $record->status === 'pending_review' &&
                        Auth::user()->can('approve_auction_batch')
                    )
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status'      => 'published',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                            'review_note' => $data['note'] ?? null,
                        ]);
                        Notification::make()
                            ->title('Batch approved & published.')
                            ->success()
                            ->send();
                    }),

                ActionsAction::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) =>
                        $record->status === 'pending_review' &&
                        Auth::user()->can('reject_auction_batch')
                    )
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->minLength(5),
                        Forms\Components\Select::make('to')
                            ->label('Move to')
                            ->options(['draft' => 'Draft', 'cancelled' => 'Cancelled'])
                            ->required()
                            ->default('draft'),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status'      => $data['to'],
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                            'review_note' => $data['reason'],
                        ]);
                        Notification::make()
                            ->title('Batch rejected.')
                            ->danger()
                            ->send();
                    }),

                ActionsAction::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->visible(fn($record) =>
                        $record->status === 'published' &&
                        Auth::user()->can('close_auction_batch')
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'closed']);
                        Notification::make()
                            ->title('Batch closed.')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                ActionsBulkActionGroup::make([
                    ActionsDeleteBulkAction::make(),
                    ActionsBulkAction::make('bulkApprove')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn() => Auth::user()->can('approve_auction_batch'))
                        ->action(function ($records) {
                            foreach ($records as $r) {
                                if ($r->status !== 'pending_review') continue;
                                $r->update([
                                    'status'      => 'published',
                                    'approved_by' => Auth::id(),
                                    'approved_at' => now(),
                                ]);
                            }
                            Notification::make()
                                ->title('Selected batches approved.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
