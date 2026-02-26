<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use App\Models\Category;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('name')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('slug')
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent Category')
                    ->relationship(
                        'parent',
                        'name',
                        fn ($query) => $query->whereNull('parent_id')
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih parent category (opsional)')
                    ->nullable()
                    ->helperText('Kosongkan jika ini adalah kategori utama'),
            ]);
    }
}
