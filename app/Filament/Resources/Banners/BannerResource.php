<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Models\Banner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Components\Grid as ComponentsGrid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;
    protected static ?string $navigationLabel = 'Banners';
    protected static string|UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                ComponentsGrid::make(1)->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('subtitle')
                        ->label('Subtitle / Description')
                        ->rows(2)
                        ->maxLength(500),
                ]),

                ComponentsGrid::make(1)->schema([
                    FileUpload::make('image_path')
                        ->label('Banner Image')
                        ->image()
                        ->disk('public')
                        ->directory('banners/' . now()->format('Y/m'))
                        ->visibility('public')
                        ->required()
                        ->maxSize(4096)
                        ->columnSpanFull(),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ])
                        ->default('draft'),
                ]),

                ComponentsGrid::make(2)->schema([
                    DateTimePicker::make('start_at')->label('Start At'),
                    DateTimePicker::make('end_at')->label('End At'),
                ])->columnSpanFull(),

                TextInput::make('position')
                    ->label('Display Order')
                    ->numeric()
                    ->default(1),

                \Filament\Forms\Components\Hidden::make('created_by')
                    ->default(fn() => Auth::id()),
                \Filament\Forms\Components\Hidden::make('updated_by')
                    ->default(fn() => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->getStateUsing(function ($record) {
                        $path = $record->image_path;
                        $path = preg_replace('#^https?://[^/]+/storage/#', '', (string) $path);
                        $path = preg_replace('#^storage/#', '', $path);
                        $path = ltrim($path, '/');

                        return $path !== '' ? asset('storage/' . $path) : null;
                    })
                    ->square()
                    ->size(80),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subtitle')
                    ->label('Subtitle')
                    ->limit(40),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Order')
                    ->sortable(),

                TextColumn::make('start_at')
                    ->label('Start')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('end_at')
                    ->label('End')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('creator.full_name')
                    ->label('Created By')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBanners::route('/'),
        ];
    }
}
