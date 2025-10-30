<?php

namespace App\Filament\Resources\LotWinners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class LotWinnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('lot_id')
                    ->required()
                    ->numeric(),
                TextInput::make('winner_user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('winning_bid_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('choosen_by')
                    ->required()
                    ->numeric(),
                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('decided_at')
                    ->required(),
            ]);
    }
}
