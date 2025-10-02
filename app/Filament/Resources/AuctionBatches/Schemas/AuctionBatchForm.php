<?php

namespace App\Filament\Resources\AuctionBatches\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AuctionBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('seller_id')
                    ->required()
                    ->numeric(),
                TextInput::make('product_id')
                    ->required()
                    ->numeric(),
                Textarea::make('title')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
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
