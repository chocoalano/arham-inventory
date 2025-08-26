<?php

namespace App\AppPanel\Clusters\Produk\Resources\Products\Schemas;

use App\AppPanel\Clusters\Produk\Resources\Suppliers\Schema\Form;
use App\Models\Inventory\Product;
use App\Models\Inventory\Supplier;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

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
                            Select::make('supplier_id')
                                ->label('Supplier')
                                ->relationship('supplier', 'name')
                                ->createOptionForm(Form::schemaForm())
                                ->searchable()
                                ->preload()
                                ->nullable(),
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
                        ->description('Tambahkan gambar untuk produk Anda. Satu gambar dapat ditandai sebagai gambar utama.') // Deskripsi tambahan
                        ->schema([
                            Repeater::make('images')
                                ->relationship()
                                ->addActionLabel('Tambah Gambar')
                                ->schema([
                                    FileUpload::make('image_path')
                                        ->label('File')
                                        ->image()
                                        ->directory(fn(Get $get) => 'products/' . $get('sku'))
                                        ->imageEditor()
                                        ->required(),
                                    Toggle::make('is_primary')
                                        ->label('Utama')
                                        ->default(false),
                                    TextInput::make('sort_order')
                                        ->numeric()
                                        ->default(0)
                                        ->label('Urutan'),
                                ])
                                ->columns(3)
                                ->orderable('sort_order')
                                ->collapsible(),
                        ]),
                ]
            );
    }
}
