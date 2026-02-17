<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        // Kolom Utama (Kiri)
                        Section::make('Informasi Kategori')
                            ->columnSpan(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state))),

                                TextInput::make('slug')
                                    ->label('Slug (URL)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        // Kolom Atribut & Status (Kanan)
                        Section::make('Status & Relasi')
                            ->columnSpan(1)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->inline(false),

                                Select::make('parent_id')
                                    ->label('Kategori Induk (Parent)')
                                    ->relationship('parent', 'name')
                                    ->searchable()
                                    ->placeholder('Pilih Induk (Opsional)'),

                                TextInput::make('sort_order')
                                    ->label('Urutan Tampilan')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        // Media & Metadata (Bawah)
                        Section::make('Media & Metadata')
                            ->columnSpanFull()
                            ->schema([
                                FileUpload::make('image_path')
                                    ->label('Gambar Kategori')
                                    ->image()
                                    ->directory('categories')
                                    ->columnSpanFull(),

                                KeyValue::make('meta')
                                    ->label('Metadata Tambahan')
                                    ->helperText('Tambahkan informasi SEO atau atribut kustom lainnya.')
                                    ->keyLabel('Key')
                                    ->valueLabel('Value')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
