<?php

namespace App\Filament\Resources\AuctionBatches\Tables;

use App\Models\User;
use App\Notifications\AuctionBatchStatusNotification;
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
use Illuminate\Support\Facades\Log;

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

                TextColumn::make('product_id')->label('Product ID')->numeric()->sortable(),

                TextColumn::make('starting_price')
                    ->label('Starting Price')
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
                    ->label('Reserve Price')
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

                TextColumn::make('created_by')->label('Creator')->sortable(),
                TextColumn::make('approved_by')->label('Approved By')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approved_at')->label('Approved At')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft'          => 'Draft',
                    'pending_review' => 'Pending Review',
                    'published'      => 'Published',
                    'cancelled'      => 'Cancelled',
                    'closed'         => 'Closed',
                ]),
            ])
            ->recordActions([

                // === EDIT ===
                ActionsEditAction::make(),

                // === SEND TO REVIEW ===
                ActionsAction::make('sendToReview')
                    ->label('Send to Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(
                        fn($record) =>
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

                // === APPROVE ===
                ActionsAction::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn($record) =>
                        $record->status === 'pending_review' &&
                        Auth::user()->can('approve_auction_batch')
                    )
                    ->form([
                        Forms\Components\Textarea::make('note')->label('Note')->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status'      => 'published',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                            'review_note' => $data['note'] ?? null,
                        ]);

                        // === Notifikasi ke seller batch ini ===
                        $seller = User::find($record->seller_id);
                        if ($seller) {
                            // Kirim database notification
                            $seller->notify(new AuctionBatchStatusNotification(
                                'Approved',
                                "Your auction batch ID {$record->id} has been approved by admin.",
                                $record->id
                            ));

                            // Kirim Filament notification (langsung muncul di UI)
                            Notification::make()
                                ->title('Auction Batch Approved')
                                ->body("Your auction batch ID {$record->id} has been approved.")
                                ->success()
                                ->icon('heroicon-o-check-circle')
                                ->sendToDatabase($seller);

                            Log::info("✅ Notification sent to seller ID: {$seller->id}");
                        } else {
                            Log::warning("⚠️ Seller not found for batch ID {$record->id}");
                        }

                        // === Notifikasi ke semua seller lain (auction baru dimulai) ===
                        $otherSellers = User::where('role', 'seller')
                            ->where('id', '!=', $record->seller_id)
                            ->where('is_active', true)
                            ->get();

                        foreach ($otherSellers as $otherSeller) {
                            // Database notification
                            $otherSeller->notify(new AuctionBatchStatusNotification(
                                'Started',
                                'A new auction has been published!',
                                $record->id
                            ));

                            // Filament notification
                            Notification::make()
                                ->title('New Auction Available')
                                ->body('A new auction has been published. Check it out!')
                                ->info()
                                ->icon('heroicon-o-megaphone')
                                ->sendToDatabase($otherSeller);
                        }

                        Log::info("📢 Notification broadcasted to " . $otherSellers->count() . " sellers.");

                        // Notifikasi ke admin yang melakukan approve
                        Notification::make()
                            ->title('Batch approved & published.')
                            ->success()
                            ->send();
                    }),

                // === REJECT ===
                ActionsAction::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(
                        fn($record) =>
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

                        $seller = User::find($record->seller_id);
                        if ($seller) {
                            // Database notification
                            $seller->notify(new AuctionBatchStatusNotification(
                                'Rejected',
                                "Your auction batch ID {$record->id} was rejected. Reason: {$data['reason']}",
                                $record->id
                            ));

                            // Filament notification
                            Notification::make()
                                ->title('Auction Batch Rejected')
                                ->body("Your auction batch ID {$record->id} was rejected.")
                                ->danger()
                                ->icon('heroicon-o-x-circle')
                                ->sendToDatabase($seller);

                            Log::info("❌ Rejection notification sent to seller ID: {$seller->id}");
                        }

                        Notification::make()
                            ->title('Batch rejected.')
                            ->danger()
                            ->send();
                    }),

                // === CLOSE ===
                ActionsAction::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->visible(
                        fn($record) =>
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
                            $approvedCount = 0;
                            
                            foreach ($records as $r) {
                                if ($r->status !== 'pending_review') continue;
                                
                                $r->update([
                                    'status'      => 'published',
                                    'approved_by' => Auth::id(),
                                    'approved_at' => now(),
                                ]);

                                // Notifikasi ke seller
                                $seller = User::find($r->seller_id);
                                if ($seller) {
                                    $seller->notify(new AuctionBatchStatusNotification(
                                        'Approved',
                                        "Your auction batch ID {$r->id} has been approved.",
                                        $r->id
                                    ));

                                    Notification::make()
                                        ->title('Auction Approved')
                                        ->body("Batch ID {$r->id} approved")
                                        ->success()
                                        ->sendToDatabase($seller);
                                }

                                $approvedCount++;
                            }

                            Notification::make()
                                ->title("$approvedCount batch(es) approved successfully.")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}