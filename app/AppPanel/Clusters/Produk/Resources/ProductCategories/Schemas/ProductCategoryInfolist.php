<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductCategories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        // Kolom Kiri: Detail Utama (Mengambil 2 dari 3 bagian)
                        Group::make()
                            ->columnSpan(2)
                            ->schema([
                                Section::make('Informasi Dasar')
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nama Kategori')
                                            ->weight('bold'),

                                        TextEntry::make('slug')
                                            ->label('Slug')
                                            ->color('gray'),

                                        TextEntry::make('description')
                                            ->label('Deskripsi')
                                            ->markdown()
                                            ->columnSpanFull()
                                            ->placeholder('Tidak ada deskripsi.'),
                                    ])->columns(2),

                                Section::make('Metadata')
                                    ->schema([
                                        KeyValueEntry::make('meta')
                                            ->label('Informasi Tambahan')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Kolom Kanan: Status & Media (Mengambil 1 dari 3 bagian)
                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                Section::make('Status & Hirarki')
                                    ->schema([
                                        IconEntry::make('is_active')
                                            ->label('Status Aktif')
                                            ->boolean(),

                                        TextEntry::make('parent.name')
                                            ->label('Induk')
                                            ->badge()
                                            ->color('info')
                                            ->placeholder('Root'),

                                        TextEntry::make('sort_order')
                                            ->label('Urutan'),
                                    ]),

                                Section::make('Media')
                                    ->schema([
                                        ImageEntry::make('image_path')
                                            ->label('Gambar')
                                            ->circular()
                                            ->size(150),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(), // Membuat Grid mengambil seluruh lebar kontainer
            ]);
    }
}