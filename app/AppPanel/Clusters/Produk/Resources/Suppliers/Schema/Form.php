<?php

namespace App\AppPanel\Clusters\Produk\Resources\Suppliers\Schema;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\App;

class Form
{
    /**
     * Skema form untuk Warehouses.
     *
     * - Validasi unik aman saat create/update (ignorable current record)
     * - Default dummy saat environment local/debug
     * - Validasi numerik & rentang untuk lat/lng
     * - UX: placeholder, helper text, max length, dll.
     */
    public static function schemaForm(): array
    {
        $isDev = fn(): bool => app()->environment(['local', 'development', 'debug', 'testing']);

        return [
            Section::make('Data Pemasok')
                ->columns(3)
                ->schema([
                    TextInput::make('code')
                        ->label('Kode')
                        ->maxLength(32)
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->unique()->numerify('CODE-#####') : null),
                    TextInput::make('name')
                        ->label('Nama')
                        ->maxLength(150)
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->company() : null),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                    TextInput::make('contact_name')
                        ->label('PIC')
                        ->maxLength(150)
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->name() : null),
                    TextInput::make('phone')
                        ->label('Telepon')
                        ->maxLength(32)
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->phoneNumber() : null),
                    TextInput::make('email')
                        ->email()
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->unique()->safeEmail() : null),
                ])
                ->columnSpanFull(),
            Section::make('Alamat')
                ->columns(3)
                ->schema([
                    TextInput::make('address')
                        ->label('Alamat')
                        ->columnSpan(3)
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->streetAddress() : null),
                    TextInput::make('district')
                        ->label('Kecamatan')
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->citySuffix() : null),
                    TextInput::make('city')
                        ->label('Kota')
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->city() : null),
                    TextInput::make('province')
                        ->label('Provinsi')
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->state() : null),
                    TextInput::make('postal_code')
                        ->label('Kode Pos')
                        ->maxLength(16)
                        ->default(fn() => App::environment(['local', 'debug']) ? fake()->postcode() : null),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('lat')
                                ->numeric()
                                ->label('Latitude')
                                ->default(fn() => App::environment(['local', 'debug']) ? fake()->latitude() : null),
                            TextInput::make('lng')
                                ->numeric()
                                ->label('Longitude')
                                ->default(fn() => App::environment(['local', 'debug']) ? fake()->longitude() : null),
                        ])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }
}
