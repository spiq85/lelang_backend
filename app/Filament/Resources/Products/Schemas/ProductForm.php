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

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('seller_id')->required()->numeric(),
            TextInput::make('product_name')->required(),
            Textarea::make('description')->columnSpanFull(),
            TextInput::make('base_price')
                ->prefix('Rp')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->default(0),
            TextInput::make('status')->required()->default('draft'),

            Section::make('Images')
                ->collapsible()
                ->schema([
                    Repeater::make('images')       // relasi hasMany: Product::images()
                        ->relationship()           // otomatis pakai method images()
                        ->orderable('sort_order')
                        ->schema([
                            FileUpload::make('image_url')
                                ->label('Image')
                                ->disk('public')                     // simpan di storage/app/public
                                ->directory('products/' . date('Y/m'))
                                ->image()
                                ->imageEditor()
                                ->openable()
                                ->downloadable()
                                ->required(),
                            TextInput::make('alt')->maxLength(120),
                            Toggle::make('is_primary')->label('Primary')->default(false),
                        ])
                        ->addActionLabel('Add Image')
                        ->collapsed(),
                ]),
        ]);
    }
}