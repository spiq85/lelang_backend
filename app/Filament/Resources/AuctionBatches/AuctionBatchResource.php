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
use Illuminate\Database\Eloquent\Builder;

class AuctionBatchResource extends Resource
{
    protected static ?string $model = AuctionBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return AuctionBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuctionBatchesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when($user->role === 'seller', function ($query) use ($user) {
                $query->where('seller_id', $user->id);
            });
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
