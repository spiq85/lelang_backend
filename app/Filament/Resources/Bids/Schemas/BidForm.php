<?php

namespace App\Filament\Resources\Bids\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BidForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('batch_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('bid_amount')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('submitted_at')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('valid'),
            ]);
    }
}
