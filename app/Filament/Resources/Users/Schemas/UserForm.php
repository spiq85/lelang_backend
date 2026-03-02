<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state)),
                TextInput::make('npwp'),
                TextInput::make('phone_number')
                    ->tel(),
                Select::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'seller' => 'Seller',
                        'bidder' => 'Bidder',
                    ])
                    ->required(),
                CheckboxList::make('roles')
                    ->label('Spatie Roles')
                    ->relationship('roles', 'name')
                    ->columns(2),
                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Untuk seller baru, aktifkan ini untuk approve akun mereka.')
                    ->required(),
                DateTimePicker::make('email_verified_at'),
            ]);
    }
}
