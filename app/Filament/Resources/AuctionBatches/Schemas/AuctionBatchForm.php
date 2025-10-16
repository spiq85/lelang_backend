<?php

namespace App\Filament\Resources\AuctionBatches\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AuctionBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('seller_id')
                    ->label('Seller')
                    ->relationship('seller', 'full_name')
                    ->visible(fn() => in_array(auth()->user()->role, ['seller', 'super_admin']))
                    ->searchable()
                    ->preload()
                    ->default(fn() => auth()->id())
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('product_id')
                    ->required()
                    ->numeric(),
                Textarea::make('title')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->label('Start Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->minDate(now())
                            ->helperText('When the auction will start'),

                        DateTimePicker::make('end_at')
                            ->label('End Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->minDate(fn($get) => $get('start_at') ?? now())
                            ->after('start_at')
                            ->helperText('When the auction will end'),
                    ]),
                Textarea::make('bid_increment_rule')
                    ->columnSpanFull(),
                Textarea::make('reserve_rule')
                    ->columnSpanFull(),
                TextInput::make('starting_price')
                    ->required()
                    ->numeric(),
                TextInput::make('reserve_price')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
