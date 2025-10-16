<?php
namespace App\AppPanel\Clusters\Produksi\Resources\ProductBoms\Components;

use Filament\Forms\Components\TextInput;

class FormOperationalCost
{
    public static function form(): array
    {
        return [
            TextInput::make('name')
                ->label('Nama Biaya Operasional')
                ->helperText('Masukkan nama biaya operasional yang mudah dikenali, misalnya: Listrik, Air, Transportasi.')
                ->required(),
            TextInput::make('price')
                ->label('Harga')
                ->numeric()
                ->helperText('Isi dengan angka tanpa tanda pemisah atau simbol mata uang. Contoh: 15000')
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, $get, $set) {
                    // Ambil semua harga dari repeater relasi operationalCosts
                    $operationalCosts = $get('operationalCosts') ?? [];
                    $total = 0;
                    foreach ($operationalCosts as $cost) {
                        $total += isset($cost['price']) ? (float)$cost['price'] : 0;
                    }
                    // Set total ke field misal 'total_operational_cost'
                    $set('total_operational_cost', $total);
                }),
        ];
    }
}
