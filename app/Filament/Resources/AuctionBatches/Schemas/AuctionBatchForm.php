<?php

namespace App\Filament\Resources\AuctionBatches\Schemas;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class AuctionBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('seller_id')
                    ->label('Seller')
                    ->relationship('seller', 'full_name')
                    ->searchable()
                    ->preload()
                    ->default(fn() => auth()->id())
                    ->disabled(fn() => auth()->user()->role === 'seller') // seller tidak boleh ubah seller_id
                    ->dehydrated(true) // tetap simpan nilai meskipun disabled
                    ->visible(fn() => in_array(auth()->user()->role, ['seller', 'super_admin'])),

                Forms\Components\TextInput::make('title')
                    ->label('Batch Title')
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('start_at')
                    ->label('Start Time')
                    ->nullable(),

                Forms\Components\DateTimePicker::make('end_at')
                    ->label('End Time')
                    ->nullable(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'published' => 'Published',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->disabled(fn() => auth()->user()->role === 'seller'),
            ]);
    }
}
