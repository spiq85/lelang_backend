<?php

namespace App\Filament\Resources\BatchLots\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use App\Models\AuctionBatch;
use App\Models\Product;
use Filament\Schemas\Components\Grid;

class BatchLotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([

                // Step 1: Lot Number
                Forms\Components\TextInput::make('lot_number')
                    ->label('Lot Number')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->reactive(),

                // Step 2: Auction Batch (pindah ke atas)
                Forms\Components\Select::make('batch_id')
                    ->label('Auction Batch')
                    ->options(function () {
                        $user = auth()->user();
                        $query = AuctionBatch::query();

                        if ($user->role === 'seller') {
                            $query->where('seller_id', $user->id)
                                  ->whereIn('status', ['draft', 'pending_review']);
                        }

                        return $query->pluck('title', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->visible(fn ($get) => filled($get('lot_number'))),

                // Step 3: Status
                Forms\Components\Select::make('status')
                    ->label('Lot Status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                        'awarded' => 'Awarded',
                        'settled' => 'Settled',
                    ])
                    ->default('open')
                    ->required()
                    ->visible(fn ($get) => filled($get('batch_id'))),

                // Step 4: Multi Product builder
                Forms\Components\Repeater::make('lot_products')
                    ->label('Products in This Lot')
                    ->visible(fn ($get) => filled($get('batch_id')))
                    ->createItemButtonLabel('Add Another Product')
                    ->collapsible()
                    ->defaultItems(0)
                    ->minItems(1)
                    ->schema([

                        Forms\Components\Select::make('product_id')
                            ->label('Select Product')
                            ->options(function () {
                                $user = auth()->user();
                                $query = Product::query();

                                if ($user->role === 'seller') {
                                    $query->where('seller_id', $user->id);
                                }

                                return $query->pluck('product_name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->distinct()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $product = Product::with('images')->find($state);

                                if ($product) {
                                    $set('starting_price', $product->base_price ?? 0);
                                    $set('reserve_price', $product->base_price ?? 0);
                                    $set('product_images', $product->images->pluck('image_url')->toArray());
                                } else {
                                    $set('starting_price', null);
                                    $set('reserve_price', null);
                                    $set('product_images', []);
                                }
                            }),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('images_preview')
                                    ->label('Product Images')
                                    ->content(function ($get) {
                                        $productId = $get('product_id');
                                        if (!$productId) {
                                            return '';
                                        }

                                        $product = Product::with('images')->find($productId);
                                        if (!$product || $product->images->isEmpty()) {
                                            return 'No images';
                                        }

                                        $imageUrls = $product->images->take(3);
                                        $html = '<div style="display: flex; gap: 8px;">';
                                        
                                        foreach ($imageUrls as $img) {
                                            $html .= '<img src="' . asset('storage/' . $img->image_url) . '" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;" />';
                                        }
                                        
                                        $html .= '</div>';
                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                                    ->columnSpan(3),
                            ])
                            ->visible(fn ($get) => filled($get('product_id'))),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('starting_price')
                                    ->label('Starting Price')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->required()
                                    ->visible(fn ($get) => filled($get('product_id'))),

                                Forms\Components\TextInput::make('reserve_price')
                                    ->label('Reserve Price')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->nullable()
                                    ->visible(fn ($get) => filled($get('product_id'))),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}