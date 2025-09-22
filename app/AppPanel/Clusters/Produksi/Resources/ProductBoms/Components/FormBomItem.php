<?php
namespace App\AppPanel\Clusters\Produksi\Resources\ProductBoms\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class FormBomItem
{
    public static function form(): array
    {
        return [
            Select::make('raw_material_id')
                ->label('Bahan Baku')
                ->helperText('Pilih bahan baku yang dibutuhkan untuk pembuatan produk.')
                ->relationship('rawMaterial', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->prefixAction(
                    fn () => Action::make('createRawMaterial')
                        ->icon('heroicon-o-plus')
                        ->tooltip('Tambah Bahan Baku Baru')
                        ->url(route('filament.app.produksi.resources.raw-materials.index'))
                        ->openUrlInNewTab()
                ),

            Select::make('unit_id')
                ->label('Satuan (UoM)')
                ->helperText('Pilih satuan pemakaian bahan baku, misalnya KG, Gram, Liter, dll.')
                ->relationship('unit', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->prefixAction(
                    fn () => Action::make('createUnits')
                        ->icon('heroicon-o-plus')
                        ->tooltip('Tambah Unit Baru')
                        ->url(route('filament.app.produksi.resources.units.index'))
                        ->openUrlInNewTab()
                ),

            TextInput::make('qty')
                ->label('Jumlah Kebutuhan')
                ->helperText('Jumlah bahan baku yang dibutuhkan per 1 kali produksi.')
                ->numeric()
                ->required(),

            TextInput::make('waste_percent')
                ->label('Persentase Susut (%)')
                ->helperText('Tambahkan persentase susut atau waste (misalnya 2 untuk 2%).')
                ->numeric()
                ->default(0.0)
                ->suffix('%'),

            TextInput::make('sort_order')
                ->label('Urutan')
                ->helperText('Tentukan urutan tampilan item BoM jika ada lebih dari satu bahan.')
                ->numeric()
                ->default(0),
        ];
    }
}
