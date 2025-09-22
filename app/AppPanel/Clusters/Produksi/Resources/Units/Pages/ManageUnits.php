<?php

namespace App\AppPanel\Clusters\Produksi\Resources\Units\Pages;

use App\AppPanel\Clusters\Produksi\Resources\Units\UnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUnits extends ManageRecords
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
