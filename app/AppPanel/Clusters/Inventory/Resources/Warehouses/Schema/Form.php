<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Warehouses\Schema;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;

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
            Section::make('Data Gudang')
                ->description('Informasi dasar mengenai gudang, termasuk kode dan nama unik untuk identifikasi.')
                ->columns(3)
                ->schema([
                    TextInput::make('code')
                        ->label('Kode')
                        ->placeholder('CTRG-001')
                        ->helperText('Huruf/angka/dash, maksimal 32 karakter dan unik.')
                        ->maxLength(32)
                        ->required()
                        // filter karakter dasar (opsional, boleh dihapus bila tidak perlu):
                        ->regex('/^[A-Z0-9\-_.]+$/')
                        ->rule('unique:warehouses,code') // rule server-side (untuk safety di luar Filament)
                        ->unique(
                            table: 'warehouses',
                            column: 'code',
                            ignorable: fn(?object $record) => $record
                        )
                        ->default(fn() => $isDev() ? Str::upper(Str::random(6)) : null),

                    TextInput::make('name')
                        ->label('Nama Gudang')
                        ->placeholder('Jakarta Utara Warehouse')
                        ->helperText('Nama gudang harus unik, maksimal 150 karakter.')
                        ->maxLength(150)
                        ->required()
                        ->rule('unique:warehouses,name') // rule server-side
                        ->unique(
                            table: 'warehouses',
                            column: 'name',
                            ignorable: fn(?object $record) => $record
                        )
                        ->default(fn() => $isDev() ? fake()->city() . ' Warehouse' : null),

                    ToggleButtons::make('is_active')
                        ->label('Aktif')
                        ->boolean()
                        ->helperText('Nonaktifkan jika gudang tidak digunakan sementara.')
                        ->grouped()
                        ->default(true),
                ])->columnSpanFull(),

            Section::make('Lokasi & Kontak')
                ->description('Informasi detail lokasi dan kontak untuk pengiriman/komunikasi.')
                ->columns(3)
                ->schema([
                    TextInput::make('address')
                        ->label('Alamat')
                        ->placeholder('Jl. Melati No. 8, Blok A')
                        ->columnSpan(3)
                        ->default(fn() => $isDev() ? fake()->streetAddress() : null),

                    TextInput::make('district')
                        ->label('Kecamatan')
                        ->placeholder('Cengkareng')
                        ->default(fn() => $isDev() ? fake()->citySuffix() : null),

                    TextInput::make('city')
                        ->label('Kota')
                        ->placeholder('Jakarta Barat')
                        ->default(fn() => $isDev() ? fake()->city() : null),

                    TextInput::make('province')
                        ->label('Provinsi')
                        ->placeholder('DKI Jakarta')
                        ->default(fn() => $isDev() ? fake()->state() : null),

                    TextInput::make('postal_code')
                        ->label('Kode Pos')
                        ->placeholder('11730')
                        ->helperText('Maksimal 16 karakter.')
                        ->maxLength(16)
                        ->default(fn() => $isDev() ? fake()->postcode() : null),

                    TextInput::make('phone')
                        ->label('Telepon')
                        ->placeholder('021-555-1234')
                        ->helperText('Maksimal 32 karakter. Contoh: 021-555-1234.')
                        ->maxLength(32)
                        ->default(fn() => $isDev() ? fake()->phoneNumber() : null),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('lat')
                                ->label('Latitude')
                                ->placeholder('-6.2000000')
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(90)
                                ->step('0.0000001')
                                ->helperText('Rentang -90 s.d. 90. Gunakan titik (.) sebagai pemisah desimal.')
                                ->default(fn() => $isDev() ? number_format((float) fake()->latitude(), 7, '.', '') : null),

                            TextInput::make('lng')
                                ->label('Longitude')
                                ->placeholder('106.8166667')
                                ->numeric()
                                ->minValue(-180)
                                ->maxValue(180)
                                ->step('0.0000001')
                                ->helperText('Rentang -180 s.d. 180. Gunakan titik (.) sebagai pemisah desimal.')
                                ->default(fn() => $isDev() ? number_format((float) fake()->longitude(), 7, '.', '') : null),
                        ])
                        ->columnSpan(3),
                ])->columnSpanFull(),
        ];
    }
}
