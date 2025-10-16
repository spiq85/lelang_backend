<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use App\Models\Category;
use App\Models\User;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Tampilkan seller hanya untuk admin/super_admin
            Select::make('seller_id')
                ->label('Seller')
                ->relationship('seller', 'full_name')
                ->visible(fn () => in_array(auth()->user()->role, ['admin', 'super_admin']))
                ->searchable()
                ->preload()
                ->default(fn () => auth()->id())
                ->disabled()
                ->dehydrated(),
            TextInput::make('product_name')
                ->required(),
            Textarea::make('description')
                ->columnSpanFull(),
            TextInput::make('base_price')
                ->prefix('Rp')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->default(0),
            TextInput::make('status')
                ->required()
                ->default('draft'),

            Select::make('categories')
                ->label('Categories')
                ->relationship('categories', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->createOptionForm([
                    TextInput::make('name')
                        ->required()
                        ->unique(Category::class, 'name'),
                ])
                ->createOptionUsing(fn (array $data) => Category::create($data)->id),

            Section::make('Images')
                ->collapsible()
                ->schema([
                    Repeater::make('images')
                        ->relationship()
                        ->orderable('sort_order')
                        ->schema([
                            FileUpload::make('image_url')
                                ->label('Image')
                                ->disk('public')
                                ->directory('products/' . date('Y/m'))
                                ->image()
                                ->imageEditor()
                                ->openable()
                                ->downloadable()
                                ->required(),
                            TextInput::make('alt')->maxLength(120),
                            Toggle::make('is_primary')
                                ->label('Primary')
                                ->default(false),
                        ])
                        ->addActionLabel('Add Image')
                        ->collapsed(),
                ]),
        ]);
    }
}
