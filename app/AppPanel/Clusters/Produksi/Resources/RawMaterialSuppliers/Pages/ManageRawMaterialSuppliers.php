<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialSuppliers\Pages;

use App\AppPanel\Clusters\Produksi\Resources\RawMaterialSuppliers\RawMaterialSupplierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRawMaterialSuppliers extends ManageRecords
{
    protected static string $resource = RawMaterialSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
