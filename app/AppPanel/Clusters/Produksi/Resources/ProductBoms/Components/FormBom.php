<?php
namespace App\AppPanel\Clusters\Produksi\Resources\ProductBoms\Components;

use App\Models\Inventory\ProductVariant;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class FormBom
{
    public static function form():array
    {
        return[
            Select::make('product_id')
                ->label('Produk')
                ->helperText('Pilih produk induk yang akan dibuat Bill of Material (BoM).')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live() // ->reactive() pada Filament < v4
                ->afterStateUpdated(function (Set $set) {
                    // Reset varian ketika produk berubah
                    $set('product_variant_id', null);
                })
                ->prefixAction(
                        fn () => Action::make('createProduk')
                            ->icon('heroicon-o-plus')
                            ->tooltip('Tambah Produk Baru')
                            ->url(route('filament.app.produk.resources.products.index'))
                            ->openUrlInNewTab()
                    ),

            Select::make('product_variant_id')
                ->label('Varian Produk (Opsional)')
                ->helperText('Jika BoM hanya untuk varian tertentu, pilih varian di sini. Kosongkan bila berlaku untuk semua varian.')
                ->options(function (Get $get) {
                    $productId = $get('product_id');
                    if (blank($productId)) {
                        return [];
                    }

                    return ProductVariant::query()
                        ->where('product_id', $productId)
                        ->orderBy('sku_variant')
                        ->pluck('sku_variant', 'id')
                        ->toArray();
                })
                ->searchable()
                ->disabled(fn (Get $get) => blank($get('product_id')))
                ->nullable()
                // Validasi: jika diisi, varian harus milik produk terpilih
                ->rule(fn (Get $get) => $get('product_id')
                    ? Rule::exists('product_variants', 'id')->where('product_id', $get('product_id'))
                    : 'nullable'
                ),

            TextInput::make('version')
                ->label('Versi BoM')
                ->helperText('Tentukan versi BoM, misalnya v1, v2, dst. Berguna untuk tracking perubahan komposisi bahan.')
                ->required()
                ->default('v1'),

            Toggle::make('is_active')
                ->label('Status Aktif')
                ->helperText('Jika aktif, BoM ini akan digunakan dalam proses produksi.')
                ->default(true)
                ->required(),

            Textarea::make('note')
                ->label('Catatan')
                ->helperText('Tambahkan catatan tambahan terkait BoM, misalnya instruksi khusus atau catatan teknis.')
                ->columnSpanFull(),
        ];
    }
}
