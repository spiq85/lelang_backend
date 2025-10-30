<?php

namespace App\Filament\Resources\LotWinners;

use App\Filament\Resources\LotWinners\Pages\CreateLotWinner;
use App\Filament\Resources\LotWinners\Pages\EditLotWinner;
use App\Filament\Resources\LotWinners\Pages\ListLotWinners;
use App\Filament\Resources\LotWinners\Schemas\LotWinnerForm;
use App\Filament\Resources\LotWinners\Tables\LotWinnersTable;
use App\Models\LotWinner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LotWinnerResource extends Resource
{
    protected static ?string $model = LotWinner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return LotWinnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LotWinnersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLotWinners::route('/'),
            'create' => CreateLotWinner::route('/create'),
            'edit' => EditLotWinner::route('/{record}/edit'),
        ];
    }
}
