<?php

namespace App\AppPanel\Clusters\Produk\Resources\Products\Schemas;

use App\AppPanel\Clusters\Produk\Resources\Suppliers\Schema\Form;
use App\Models\Inventory\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Section::make('Data Produk')
                        ->description('Informasi dasar mengenai produk, termasuk nama, merek, dan deskripsi.') // Deskripsi tambahan
                        ->columns(3)
                        ->schema([
                            TextInput::make('sku')
                                ->label('SKU')
                                ->required()
                                ->unique()
                                ->default(Product::generateUniqueSku()),
                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true),
                            TextInput::make('name')
                                ->label('Nama Produk')
                                ->required()
                                ->columnSpan(2)
                                ->default(fn() => app()->environment(['local', 'debug']) ? fake()->words(3, true) : null), // Autofill dengan 3 kata acak
                            TextInput::make('brand')
                                ->label('Brand')
                                ->default(fn() => app()->environment(['local', 'debug']) ? fake()->company() : null), // Autofill dengan nama perusahaan acak
                            Select::make('product_category_id')
                                ->label('Kategori')
                                ->relationship('category', 'name')
                                ->createOptionForm(ProductForm::schemaCategoryForm())
                                ->searchable()
                                ->preload()
                                ->nullable(),
                            TextInput::make('model')
                                ->label('Model')
                                ->default(fn() => app()->environment(['local', 'debug']) ? fake()->bothify('###??-###??') : null), // Autofill dengan format acak
                            Textarea::make('description')
                                ->label('Deskripsi')
                                ->rows(3)
                                ->columnSpanFull()
                                ->default(fn() => app()->environment(['local', 'debug']) ? fake()->paragraph(2) : null), // Autofill dengan 2 paragraf acak
                        ]),

                    Section::make('Gambar')
                        ->description('Tambahkan gambar untuk produk Anda. Satu gambar dapat ditandai sebagai gambar utama.')
                        ->schema([
                            Repeater::make('images')
                                ->relationship('images')
                                ->addActionLabel('Tambah Gambar')
                                ->schema([
                                    FileUpload::make('image_path')
                                        ->label('File')
                                        ->image()
                                        ->directory('products')
                                        ->disk('public')
                                        ->required()
                                        ->columnSpanFull(),
                                    Toggle::make('is_primary')
                                        ->label('Utama')
                                        ->default(false),
                                    TextInput::make('sort_order')
                                        ->numeric()
                                        ->default(0)
                                        ->label('Urutan'),
                                ])
                                ->columns(2)
                                ->orderable('sort_order')
                                ->collapsible(),
                        ]),
                ]
            );
    }

    public static function schemaCategoryForm(): array
    {
        return [
            TextInput::make('name')
                ->label('Nama Kategori')
                ->required()
                ->unique()
                ->default(fn() => app()->environment(['local', 'debug']) ? fake()->words(3, true) : null), // Autofill dengan 3 kata acak
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique()
                ->default(fn() => app()->environment(['local', 'debug']) ? fake()->slug() : null), // Autofill dengan slug acak
            Textarea::make('description')
                ->label('Deskripsi')
                ->rows(3)
                ->columnSpanFull()
                ->default(fn() => app()->environment(['local', 'debug']) ? fake()->paragraph(2) : null), // Autofill dengan 2 paragraf acak
            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ];
    }
}
