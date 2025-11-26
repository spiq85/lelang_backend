<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\IconPosition;
use App\Models\Category;
use App\Models\User;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Product Details')
                ->tabs([
                    // Tab 1: Basic Information
                    Tab::make('Basic Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 2,
                            ])
                                ->schema([
                                    // Seller field (only for admin/super_admin)
                                    Select::make('seller_id')
                                        ->label('Seller')
                                        ->relationship('seller', 'full_name')
                                        ->visible(fn () => in_array(auth()->user()->role, ['admin', 'super_admin']))
                                        ->searchable()
                                        ->preload()
                                        ->default(fn () => auth()->id())
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpanFull()
                                        ->helperText('The seller responsible for this product'),

                                    TextInput::make('product_name')
                                        ->label('Product Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Enter product name')
                                        ->columnSpan([
                                            'default' => 'full',
                                            'md' => 1,
                                        ])
                                        ->live(onBlur: true),

                                    Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            'draft' => 'Draft',
                                            'published' => 'Published',
                                            'archived' => 'Archived',
                                        ])
                                        ->required()
                                        ->default('draft')
                                        ->native(false)
                                        ->columnSpan([
                                            'default' => 'full',
                                            'md' => 1,
                                        ]),

                                    Textarea::make('description')
                                        ->label('Product Description')
                                        ->rows(5)
                                        ->maxLength(1000)
                                        ->placeholder('Describe your product in detail...')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // Tab 2: Pricing & Categories
                    Tab::make('Pricing & Categories')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            Section::make('Pricing')
                                ->description('Set the base price for this product')
                                ->icon('heroicon-o-currency-dollar')
                                ->collapsible()
                                ->schema([
                                    TextInput::make('base_price')
                                        ->label('Base Price')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->numeric()
                                        ->default(0)
                                        ->required()
                                        ->minValue(0)
                                        ->helperText('Enter the base price in Indonesian Rupiah'),
                                ]),

                            Section::make('Categories')
                                ->description('Organize your product by selecting relevant categories')
                                ->icon('heroicon-o-folder')
                                ->collapsible()
                                ->schema([
                                    Select::make('categories')
                                        ->label('Product Categories')
                                        ->relationship('categories', 'name')
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->createOptionForm([
                                            TextInput::make('name')
                                                ->label('Category Name')
                                                ->required()
                                                ->unique(Category::class, 'name')
                                                ->maxLength(255),
                                        ])
                                        ->createOptionUsing(fn (array $data) => Category::create($data)->id)
                                        ->helperText('Select or create new categories for this product'),
                                ]),
                        ]),

                    // Tab 3: Images & Media
                    Tab::make('Images & Media')
                        ->icon('heroicon-o-photo')
                        ->badge(fn ($record) => $record?->images()->count() ?? 0)
                        ->badgeColor('success')
                        ->schema([
                            Section::make('Product Images')
                                ->description('Upload and manage product images. Set one as primary for display.')
                                ->icon('heroicon-o-camera')
                                ->collapsible()
                                ->collapsed(false)
                                ->schema([
                                    Repeater::make('images')
                                        ->relationship()
                                        ->orderable('sort_order')
                                        ->reorderableWithButtons()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['alt'] ?? 'Image')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    FileUpload::make('image_url')
                                                        ->label('Image')
                                                        ->disk('public')
                                                        ->directory('products/' . date('Y/m'))
                                                        ->image()
                                                        ->imageEditor()
                                                        ->imageEditorAspectRatios([
                                                            null,
                                                            '16:9',
                                                            '4:3',
                                                            '1:1',
                                                        ])
                                                        ->maxSize(5120) // 5MB
                                                        ->openable()
                                                        ->downloadable()
                                                        ->required()
                                                        ->columnSpan(1)
                                                        ->helperText('Max size: 5MB. Recommended: 1200x1200px'),
                                                ]),
                                        ])
                                        ->addActionLabel('Add New Image')
                                        ->reorderableWithDragAndDrop(false)
                                        ->minItems(1)
                                        ->maxItems(10)
                                        ->defaultItems(0),
                                ]),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTab()
                ->id('product-form-tabs'),
        ]);
    }
}