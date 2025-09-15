<?php

namespace App\AppPanel\Clusters\Finance\Resources\CostCenters\Pages;

use App\AppPanel\Clusters\Finance\Resources\CostCenters\CostCenterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCostCenters extends ManageRecords
{
    protected static string $resource = CostCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
