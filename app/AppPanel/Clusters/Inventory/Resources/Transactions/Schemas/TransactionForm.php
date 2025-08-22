<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Transactions\Schemas;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Warehouse;
use Filament\Forms\Components\{DateTimePicker, Hidden, Placeholder, Repeater, Select, Textarea, TextInput};
use Filament\Schemas\Components\{Grid, Section};
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Transaksi')
                ->description('Informasi dasar untuk identifikasi dan pencatatan transaksi.')
                ->columns(3)
                ->schema([
                    TextInput::make('reference_number')
                        ->label('Nomor Referensi')
                        ->helperText('Nomor unik untuk melacak transaksi. Dibuat otomatis oleh sistem.')
                        ->default(fn() => 'TRX-' . Str::upper(Str::random(8)))
                        ->readOnly()
                        ->unique(ignoreRecord: true)
                        ->dehydrated(), // pastikan terkirim,

                    Select::make('type')
                        ->label('Tipe')
                        ->helperText('Pilih tipe transaksi yang akan dicatat (mis. Penjualan, Pemindahan, Pengembalian).')
                        ->options([
                            'penjualan' => 'Penjualan',
                            'pemindahan' => 'Pemindahan',
                            'pengembalian' => 'Pengembalian',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                            $user = Auth::user();

                            // Non-superadmin: kunci gudang sumber sesuai gudang user
                            if ($user && !$user->hasRole('Superadmin')) {
                                $set('source_warehouse_id', $user->warehouse_id);
                            }
                            // Reset baris detail supaya tidak ada data "nyangkut"
                            $rows = $get('details') ?? [];
                            foreach (array_keys($rows) as $i) {
                                // sinkron gudang baris
                                if ($user && !$user->hasRole('Superadmin')) {
                                    $set("details.$i.warehouse_id", $user->warehouse_id);
                                }

                                // reset dependent fields
                                $set("details.$i.product_id", null);
                                $set("details.$i.product_variant_id", null);
                                $set("details.$i.qty", 1);
                                $set("details.$i.price", 0);
                                $set("details.$i.line_total", 0);
                            }
                        }),

                    DateTimePicker::make('transaction_date')
                        ->label('Tanggal Transaksi')
                        ->helperText('Tanggal dan waktu saat transaksi ini terjadi.')
                        ->default(now())
                        ->required()
                        ->dehydrated(),
                ]),

            Section::make('Gudang & Pelanggan')
                ->description('Informasi gudang yang terlibat dan detail pelanggan jika diperlukan.')
                ->schema([
                    Grid::make(12)->schema([
                        Select::make('source_warehouse_id')
                            ->label('Gudang Sumber')
                            ->options(fn() => Warehouse::pluck('name', 'id'))
                            ->searchable()->preload()
                            ->columnSpan(fn(Get $get) => $get('type') === 'penjualan' ? 4 : 6)
                            ->live()
                            ->required()
                            ->default(fn() => Auth::user()?->warehouse_id)
                            ->disabled(fn() => Auth::user() && !Auth::user()->hasRole('Superadmin'))
                            ->dehydrated()
                            ->afterStateUpdated(function (Set $set, Get $get, int $state) {
                                // Sinkronkan warehouse per baris repeater + reset item
                                $rows = $get('details') ?? [];
                                foreach (array_keys($rows) as $i) {
                                    $set("details.$i.warehouse_id", $state);
                                    $set("details.$i.product_id", null);
                                    $set("details.$i.product_variant_id", null);
                                    $set("details.$i.price", 0);
                                    $set("details.$i.line_total", 0);
                                }
                            }),

                        Select::make('destination_warehouse_id')
                            ->label('Gudang Tujuan')
                            ->helperText('Gudang tempat produk dipindahkan atau dikembalikan.')
                            ->options(fn() => Warehouse::where('id', '!=', Auth::user()->warehouse_id)
                                ->pluck('name', 'id'))
                            ->searchable()->preload()
                            ->columnSpan(fn(Get $get) => $get('type') === 'penjualan' ? 4 : 6)
                            ->visible(fn(Get $get) => in_array($get('type'), ['pemindahan', 'pengembalian', 'penyesuaian']))
                            ->required(fn(Get $get) => in_array($get('type'), ['pemindahan', 'pengembalian', 'penyesuaian']))
                            // Tetap kirim nilai: jika tidak relevan → null
                            ->dehydrateStateUsing(fn(Get $get, $state) => in_array($get('type'), ['pemindahan', 'pengembalian', 'penyesuaian']) ? $state : null),

                        TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->columnSpan(4)
                            ->visible(fn(Get $get) => $get('type') === 'penjualan')
                            ->dehydrateStateUsing(fn(Get $get, $state) => $get('type') === 'penjualan' ? $state : null),

                        TextInput::make('customer_phone')
                            ->label('Telepon Pelanggan')
                            ->tel()
                            ->columnSpan(4)
                            ->visible(fn(Get $get) => $get('type') === 'penjualan')
                            ->dehydrateStateUsing(fn(Get $get, $state) => $get('type') === 'penjualan' ? $state : null),

                        Textarea::make('customer_full_address')
                            ->label('Alamat Pelanggan')
                            ->rows(2)
                            ->columnSpan(12)
                            ->visible(fn(Get $get) => $get('type') === 'penjualan')
                            ->dehydrateStateUsing(fn(Get $get, $state) => $get('type') === 'penjualan' ? $state : null),
                    ]),
                ]),

            Section::make('Detail Item')
                ->description('Daftar produk dan kuantitas yang terlibat dalam transaksi.')
                // tetap tampil setelah pilih gudang sumber
                ->visible(fn(Get $get) => filled($get('source_warehouse_id')))
                ->schema([
                    Repeater::make('details')
                        ->reorderable(false)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Tambah Item')
                        ->columns(12)
                        ->schema([
                            Select::make('warehouse_id')
                                ->label('Gudang (baris)')
                                ->options(fn() => Warehouse::pluck('name', 'id'))
                                ->default(fn() => Auth::user()?->warehouse_id)
                                ->disabled(fn() => Auth::user() && !Auth::user()->hasRole('Superadmin'))
                                ->searchable()->preload()
                                ->required()
                                ->live()
                                ->dehydrated()
                                ->columnSpan(4),

                            Select::make('product_id')
                                ->label('Produk')
                                ->helperText('Hanya produk yang tersedia di gudang baris ini.')
                                ->options(function (Get $get) {
                                    $wid = (int) ($get('warehouse_id') ?? 0);
                                    if ($wid <= 0)
                                        return [];
                                    return Product::availableInWarehouse($wid)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->searchable()->preload()
                                ->required()
                                ->live()
                                ->dehydrated()
                                ->afterStateUpdated(fn(Set $set) => $set('product_variant_id', null))
                                ->columnSpan(4),

                            Select::make('product_variant_id')
                                ->label('Varian')
                                ->options(function (Get $get) {
                                    $pid = $get('product_id');
                                    $wid = (int) ($get('warehouse_id') ?? 0);
                                    if (blank($pid) || $wid <= 0)
                                        return [];

                                    return ProductVariant::query()
                                        ->where('product_id', $pid)
                                        ->whereHas('stocks', function ($q) use ($wid) {
                                            $q->where('warehouse_id', $wid)
                                                ->whereRaw('(COALESCE(qty,0) - COALESCE(reserved_qty,0)) > 0');
                                        })
                                        ->orderBy('sku_variant')
                                        ->pluck('sku_variant', 'id');
                                })
                                ->searchable()->preload()
                                ->disabled(fn(Get $get) => blank($get('product_id')) || blank($get('warehouse_id')))
                                ->required()
                                ->live()
                                ->dehydrated()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $price = (int) (ProductVariant::query()->whereKey($state)->value('price') ?? 0);
                                    $qty = (int) ($get('qty') ?? 0);
                                    $set('price', $price);
                                    $set('line_total', $price * $qty);
                                })
                                ->afterStateHydrated(function (Get $get, Set $set, $state) {
                                    $qty = (int) ($get('qty') ?? 0);
                                    $price = (int) ($get('price') ?? 0);
                                    if ($state && $price === 0) {
                                        $price = (int) (ProductVariant::query()->whereKey($state)->value('price') ?? 0);
                                        $set('price', $price);
                                    }
                                    $set('line_total', $qty * $price);
                                })
                                ->columnSpan(4),

                            TextInput::make('qty')
                                ->label('Kuantitas')
                                ->numeric()->minValue(1)->default(1)
                                ->required()
                                ->live(debounce: 250)
                                ->dehydrated()
                                ->afterStateUpdated(fn(Get $get, Set $set, $state) => $set('line_total', (int) ($get('price') ?? 0) * (int) ($state ?? 0)))
                                ->columnSpan(6),

                            TextInput::make('price')
                                ->label('Harga')
                                ->numeric()->prefix('Rp')->minValue(0)->default(0)
                                ->live(debounce: 250)
                                ->dehydrated()
                                ->afterStateUpdated(fn(Get $get, Set $set, $state) => $set('line_total', (int) ($get('qty') ?? 0) * (int) ($state ?? 0)))
                                ->columnSpan(6),

                            Hidden::make('line_total')
                                ->default(0)
                                ->dehydrated(true),

                            Placeholder::make('subtotal_display')
                                ->label('Subtotal')
                                ->reactive()
                                ->content(fn(Get $get) => 'Rp ' . number_format((int) ($get('line_total') ?? 0), 0, ',', '.'))
                                ->columnSpan(12),
                        ]),
                ]),

            Section::make('ringkasan')
                ->label('Ringkasan')
                ->description('Total keseluruhan transaksi dan catatan tambahan.')
                ->columns(2)
                ->schema([
                    Placeholder::make('grand_total_display')
                        ->label('Grand Total')
                        ->reactive()
                        ->content(function (Get $get) {
                            $sum = 0;
                            foreach (($get('details') ?? []) as $row) {
                                $sum += (int) ($row['line_total'] ?? 0);
                            }
                            return 'Rp ' . number_format($sum, 0, ',', '.');
                        }),

                    Textarea::make('remarks')
                        ->label('Catatan')
                        ->rows(2)
                        ->dehydrated()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
