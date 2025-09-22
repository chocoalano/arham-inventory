<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterials\Pages;

use App\AppPanel\Clusters\Produksi\Resources\RawMaterials\RawMaterialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRawMaterials extends ManageRecords
{
    protected static string $resource = RawMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
