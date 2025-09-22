<?php
namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterials\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class RawMaterialImageForm
{
    public static function form():array
    {
        return [
            FileUpload::make('image_path')
                ->label('Gambar')
                ->helperText('Unggah file gambar untuk bahan baku ini. Pastikan format file adalah JPG atau PNG.')
                ->image()
                ->directory('raw-materials')
                ->columnSpanFull()
                ->required(),

            Toggle::make('is_primary')
                ->label('Gambar Utama')
                ->helperText('Aktifkan jika gambar ini adalah gambar utama yang mewakili bahan baku.')
                ->default(false)
                ->required(),

            TextInput::make('sort_order')
                ->label('Urutan Tampilan')
                ->helperText('Tentukan urutan tampilan gambar jika ada lebih dari satu. Nilai lebih kecil tampil lebih awal.')
                ->numeric()
                ->default(0)
                ->required(),
        ];
    }
}
