<?php

namespace App\Filament\Resources\BatchLots;

use App\Filament\Resources\BatchLots\Pages\CreateBatchLot;
use App\Filament\Resources\BatchLots\Pages\EditBatchLot;
use App\Filament\Resources\BatchLots\Pages\ListBatchLots;
use App\Filament\Resources\BatchLots\Schemas\BatchLotForm;
use App\Filament\Resources\BatchLots\Tables\BatchLotsTable;
use App\Models\BatchLot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BatchLotResource extends Resource
{
    protected static ?string $model = BatchLot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'lot_number';

    public static function form(Schema $schema): Schema
    {
        return BatchLotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BatchLotsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when($user->role === 'seller', function (Builder $query) use ($user) {
                $query->whereHas('batch', fn ($q) => $q->where('seller_id', $user->id));
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBatchLots::route('/'),
            'create' => CreateBatchLot::route('/create'),
            'edit' => EditBatchLot::route('/{record}/edit'),
        ];
    }
}