<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry as ComponentsImageEntry;
use Filament\Infolists\Components\RepeatableEntry as ComponentsRepeatableEntry;
use Filament\Infolists\Components\TextEntry as ComponentsTextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // ⬅️ v4: Schema dipakai untuk form & infolist
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    // FORM
    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    // TABLE
    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    // VIEW (modal) — definisikan INFOLIST di sini    
    public static function infolist(\Filament\Schemas\Schema $infolist): \Filament\Schemas\Schema
{
    return $infolist->schema([
        ComponentsTextEntry::make('product_name')->label('Product'),
        ComponentsTextEntry::make('seller.name')->label('Seller'),
        ComponentsTextEntry::make('base_price')->money('IDR', true),
        ComponentsTextEntry::make('status')->badge(),
        ComponentsTextEntry::make('description')->columnSpanFull(),

        ComponentsRepeatableEntry::make('images')
            ->label('Images')
            ->state(function (\App\Models\Product $record) {
                return $record->images->map(function ($img) {
                    // path mentah dari DB
                    $p = (string) ($img->image_url ?? '');

                    // normalisasi: buang prefix 'http(s)://.../storage/' atau 'storage/'
                    $p = preg_replace('#^https?://[^/]+/storage/#', '', $p);
                    $p = preg_replace('#^storage/#', '', $p);
                    $p = ltrim($p, '/');

                    // bentuk URL publik tanpa Storage::url()
                    $url = $p !== '' ? asset('storage/'.$p) : null;

                    return [
                        'src'   => $url,                 // dipakai ImageEntry
                        'debug' => $url ?? '(empty)',    // debug (hapus kalau sudah ok)
                    ];
                })->all();
            })
            ->schema([
                ComponentsImageEntry::make('src')
                    ->label('Image')
                    ->square(),
            ])
            ->columns(4)
            ->columnSpanFull(),
    ]);
}

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
