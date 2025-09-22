<?php
namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterials\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class RawMaterialForm
{
    public static function form():array
    {
        return [
            Select::make('category_id')
                ->label('Kategori Bahan Baku')
                ->helperText('Pilih kategori bahan baku. Klik ikon ➕ untuk menambahkan kategori baru.')
                ->relationship('category', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->prefixAction(
                    fn () => Action::make('createRawMaterialCategory')
                        ->icon('heroicon-o-plus')
                        ->tooltip('Tambah Kategori Bahan Baku Baru')
                        ->url(route('filament.app.produksi.resources.raw-material-categories.index'))
                        ->openUrlInNewTab()
                ),

            Select::make('default_unit_id')
                ->label('Satuan Utama (UoM)')
                ->helperText('Pilih satuan utama untuk bahan baku ini. Klik ikon ➕ untuk menambahkan satuan baru.')
                ->relationship('defaultUnit', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->prefixAction(
                    fn () => Action::make('createUnit')
                        ->icon('heroicon-o-plus')
                        ->tooltip('Tambah Satuan Baru')
                        ->url(route('filament.app.produksi.resources.units.index'))
                        ->openUrlInNewTab()
                ),

            TextInput::make('code')
                ->label('Kode Bahan Baku')
                ->helperText('Masukkan kode unik untuk bahan baku, misalnya RM-001 atau BBK-2024.')
                ->required()
                ->maxLength(64),

            TextInput::make('name')
                ->label('Nama Bahan Baku')
                ->helperText('Masukkan nama bahan baku, misalnya “Gula Pasir” atau “Tepung Terigu”.')
                ->required()
                ->maxLength(200),

            Textarea::make('spec')
                ->label('Spesifikasi (Opsional)')
                ->helperText('Tambahkan spesifikasi teknis bahan baku seperti kualitas, standar, atau catatan khusus.')
                ->nullable()
                ->columnSpanFull(),

            TextInput::make('min_stock')
                ->label('Stok Minimum')
                ->helperText('Tentukan jumlah minimum stok bahan baku. Sistem bisa memberi peringatan jika stok di bawah batas ini.')
                ->required()
                ->numeric()
                ->default(0.0),

            Toggle::make('is_active')
                ->label('Status Aktif')
                ->helperText('Aktifkan jika bahan baku ini dapat digunakan dalam sistem. Nonaktifkan jika sudah tidak dipakai.')
                ->default(true)
                ->required(),
        ];
    }
}
