<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialBatches\Pages;

use App\AppPanel\Clusters\Produksi\Resources\RawMaterialBatches\RawMaterialBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRawMaterialBatches extends ManageRecords
{
    protected static string $resource = RawMaterialBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
