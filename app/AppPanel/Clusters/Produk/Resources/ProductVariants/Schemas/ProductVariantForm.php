<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductVariants\Schemas;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Supplier;
use App\Models\Inventory\Warehouse;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Varian Produk')
                ->description('Detail spesifik untuk varian produk, termasuk kode unik, warna, dan ukuran.')
                ->columns(3)
                ->schema([
                    Select::make('product_id')
                        ->label('Produk')
                        ->relationship('product', 'sku')
                        ->createOptionForm([
                            Section::make('Data Produk')
                                ->description('Informasi dasar mengenai produk, termasuk nama, merek, dan deskripsi.') // Deskripsi tambahan
                                ->columns(3)
                                ->schema([
                                    Select::make('supplier_id')
                                        ->label('Supplier')
                                        ->options(fn() => Supplier::query()->orderBy('name')->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->nullable(),
                                    TextInput::make('sku')
                                        ->label('SKU')
                                        ->required()
                                        ->unique()
                                        ->default(fn() => app()->environment(['local', 'debug']) ? strtoupper(Str::random(10)) : null), // Autofill dengan random string
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
                        ])
                        ->helperText('Pilih nama produk utama yang akan memiliki varian ini. Jika produk tidak ada, tambahkan dulu di halaman produk.')
                        ->searchable()->preload()->required()->live()
                        ->afterStateUpdated(fn(Set $set, Get $get) => self::maybeAutofillSku($set, $get, true)),

                    TextInput::make('sku_variant')
                        ->label('SKU Varian')
                        ->helperText('Nomor SKU (Stock Keeping Unit) unik untuk varian produk ini. Sistem dapat mengisi otomatis, atau Anda bisa isi manual.')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            $set('sku_variant', strtoupper(trim(preg_replace('/[^A-Z0-9]+/', '-', (string) $state))));
                        }),

                    TextInput::make('barcode')
                        ->label('Barcode')
                        ->helperText('Masukkan nomor barcode produk jika tersedia. Barcode harus unik dan dapat digunakan untuk pemindaian.')
                        ->unique(ignoreRecord: true)
                        ->nullable(),

                    TextInput::make('color')
                        ->label('Warna')
                        ->helperText('Tentukan warna spesifik untuk varian produk ini, misalnya "Merah", "Biru", atau "Hijau".')
                        ->required()
                        ->live(debounce: 200)
                        ->afterStateUpdated(fn(Set $set, Get $get) => self::maybeAutofillSku($set, $get, true)),

                    Select::make('size')
                        ->label('Ukuran')
                        ->helperText('Pilih ukuran untuk varian ini. Kombinasi Produk + Warna + Ukuran harus unik untuk setiap varian.')
                        ->options(array_combine(ProductVariant::SIZES, ProductVariant::SIZES))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn(Set $set, Get $get) => self::maybeAutofillSku($set, $get, true))
                        ->rule(function (Get $get, ?Model $record) {
                            return Rule::unique('product_variants', 'size')
                                ->where(
                                    fn($q) => $q
                                        ->where('product_id', $get('product_id'))
                                        ->where('color', $get('color'))
                                )
                                ->ignore($record?->id);
                        }),

                    TextInput::make('cost_price')
                        ->label('Harga Pokok')
                        ->helperText('Harga modal per unit untuk varian produk ini. Masukkan dalam angka (tanpa simbol mata uang).')
                        ->default(fn() => app()->environment(['local', 'debug']) ? fake()->randomFloat(2, 10000, 100000) : 0)
                        ->numeric()->minValue(0)->prefix('Rp'),

                    TextInput::make('price')
                        ->label('Harga Jual')
                        ->helperText('Harga jual per unit untuk varian produk ini. Masukkan dalam angka (tanpa simbol mata uang).')
                        ->default(fn() => app()->environment(['local', 'debug']) ? fake()->randomFloat(2, 11000, 110000) : 0)
                        ->numeric()->minValue(0)->prefix('Rp'),

                    Select::make('status')
                        ->label('Status')
                        ->helperText('Pilih status saat ini untuk varian produk. Varian "Aktif" akan terlihat dan dapat dijual.')
                        ->options([
                            'active' => 'Aktif',
                            'inactive' => 'Nonaktif',
                            'discontinued' => 'Discontinued',
                        ])
                        ->default('active'),

                    TextInput::make('qty')
                        ->label('Jumlah Quantity')
                        ->numeric()
                        ->helperText('Masukan jumlah quantity untuk varian ini dengan nilai numerik (cth: 1)'),

                    Select::make('set_warehouse')
                        ->label('Pilih Lokasi Gudang')
                        ->helperText('Pilih gudang tempat varian produk ini akan disimpan. Ini akan menjadi gudang default.')
                        ->options(Warehouse::all()->pluck('name', 'id'))
                        ->suffixAction(
                            Action::make('addWarehouse')
                                ->icon('heroicon-m-plus')
                                ->tooltip('Tambah Warehouse Baru')
                                ->url(route('filament.app.inventory.resources.warehouses.index'))
                                ->openUrlInNewTab()
                        )
                        ->required(),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * Selalu generate SKU unik dari: PRODUCT.SKU + COLOR + SIZE
     * Hanya jalan jika product + color + size sudah terisi.
     * $force=true → menimpa nilai lama (autofill agresif).
     */
    protected static function maybeAutofillSku(Set $set, Get $get, bool $force = true): void
    {
        $productId = $get('product_id');
        $color = trim((string) $get('color'));
        $size = trim((string) $get('size'));

        if (!$productId || $color === '' || $size === '') {
            return; // tunggu sampai lengkap
        }

        $product = Product::query()->select(['id', 'sku'])->find($productId);
        if (!$product?->sku)
            return;

        $norm = function (?string $v) {
            $v = strtoupper((string) $v);
            $v = preg_replace('/[^A-Z0-9]+/', '-', $v ?? '');
            return trim($v, '-');
        };

        $base = implode('-', [$norm($product->sku), $norm($color), $norm($size)]);

        // Jika force → selalu generate unik & timpa
        $unique = self::makeUniqueSkuVariant($base, (int) ($get('id') ?? 0));
        $set('sku_variant', $unique);
    }

    /**
     * Pastikan unik di DB. Jika "BASE" sudah ada, coba "BASE-2", "BASE-3", dst.
     */
    protected static function makeUniqueSkuVariant(string $candidateBase, ?int $excludeId = null): string
    {
        $exists = function (string $sku) use ($excludeId): bool {
            return \App\Models\Inventory\ProductVariant::query()
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->where('sku_variant', $sku)
                ->exists();
        };

        if (!$exists($candidateBase))
            return $candidateBase;

        for ($i = 2; $i < 10000; $i++) {
            $try = "{$candidateBase}-{$i}";
            if (!$exists($try))
                return $try;
        }

        return $candidateBase . '-' . uniqid();
    }
}
