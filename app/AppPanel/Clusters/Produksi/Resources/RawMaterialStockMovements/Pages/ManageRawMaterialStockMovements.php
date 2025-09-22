<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialStockMovements\Pages;

use App\AppPanel\Clusters\Produksi\Resources\RawMaterialStockMovements\RawMaterialStockMovementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRawMaterialStockMovements extends ManageRecords
{
    protected static string $resource = RawMaterialStockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
