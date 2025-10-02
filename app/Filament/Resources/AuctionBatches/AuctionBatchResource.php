<?php

namespace App\Filament\Resources\AuctionBatches;

use App\Filament\Resources\AuctionBatches\Pages\CreateAuctionBatch;
use App\Filament\Resources\AuctionBatches\Pages\EditAuctionBatch;
use App\Filament\Resources\AuctionBatches\Pages\ListAuctionBatches;
use App\Filament\Resources\AuctionBatches\Schemas\AuctionBatchForm;
use App\Filament\Resources\AuctionBatches\Tables\AuctionBatchesTable;
use App\Models\AuctionBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AuctionBatchResource extends Resource
{
    protected static ?string $model = AuctionBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AuctionBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuctionBatchesTable::configure($table);
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
            'index' => ListAuctionBatches::route('/'),
            'create' => CreateAuctionBatch::route('/create'),
            'edit' => EditAuctionBatch::route('/{record}/edit'),
        ];
    }
}
