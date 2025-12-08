<?php

namespace App\Filament\Resources\BatchLots\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use App\Models\AuctionBatch;
use App\Models\Product;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class BatchLotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
    Forms\Components\Select::make('batch_id')
        ->label('Auction Batch')
        ->relationship('batch', 'title')
        ->searchable()
        ->preload()
        ->required()
        ->reactive(),

    Forms\Components\TextInput::make('lot_number')
        ->label('Lot Number')
        ->numeric()
        ->required()
        ->unique('batch_lots', 'lot_number', ignorable: fn ($record) => $record)
        ->helperText('Nomor unik dalam satu batch'),

    Section::make('Produk dalam Lot Ini')
        ->collapsible()
        ->collapsed(fn ($record) => $record?->lotProducts->count() > 8)
        ->schema([
            Forms\Components\Repeater::make('lotProducts')
                ->relationship('lotProducts') // otomatis create/update/delete di batch_lot_products
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label('Pilih Produk')
                        ->options(\App\Models\Product::pluck('product_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->distinct()
                        ->afterStateUpdated(function ($state, $set) {
                            if (!$state) return;
                            $product = \App\Models\Product::find($state);
                            $price = $product?->base_price ?? 0;
                            $set('starting_price', $price);
                            $set('reserve_price', $price);
                        }),

                    Forms\Components\Placeholder::make('image_preview')
                        ->label('Preview Gambar')
                        ->content(function ($get) {
                            $id = $get('product_id');
                            if (!$id) return new HtmlString('<em>Pilih produk untuk melihat gambar</em>');

                            $product = \App\Models\Product::with('images')->find($id);
                            if (!$product || $product->images->isEmpty()) {
                                return new HtmlString('<em>Tidak ada gambar</em>');
                            }

                            $html = '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">';
                            foreach ($product->images->sortBy('sort_order')->take(5) as $img) {
                                $url = asset('storage/' . $img->image_url);
                                $html .= "<img src=\"{$url}\" style=\"width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;\" />";
                            }
                            $html .= '</div>';
                            return new HtmlString($html);
                        })
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('starting_price')
                            ->label('Starting Price')
                            ->numeric()
                            ->prefix('Rp ')
                            ->required()
                            ->minValue(0),

                        Forms\Components\TextInput::make('reserve_price')
                            ->label('Reserve Price')
                            ->numeric()
                            ->prefix('Rp ')
                            ->nullable()
                            ->helperText('Kosongkan jika tidak ada reserve'),
                    ]),
                ])
                ->columns(1)
                ->collapsible()
                ->cloneable()
                ->reorderableWithDragAndDrop(false)
                ->itemLabel(fn (array $state) => \App\Models\Product::find($state['product_id'] ?? null)?->product_name ?? 'Produk Baru')
                ->minItems(1)
                ->maxItems(50)
                ->addActionLabel('Tambah Produk Lagi')
                ->deleteAction(fn ($action) => $action->requiresConfirmation()),
        ]),
]);
    }
}