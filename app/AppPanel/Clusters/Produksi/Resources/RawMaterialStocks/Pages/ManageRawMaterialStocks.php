<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialStocks\Pages;

use App\AppPanel\Clusters\Produksi\Resources\RawMaterialStocks\RawMaterialStockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRawMaterialStocks extends ManageRecords
{
    protected static string $resource = RawMaterialStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
