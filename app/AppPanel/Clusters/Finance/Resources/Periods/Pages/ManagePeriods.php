<?php

namespace App\AppPanel\Clusters\Finance\Resources\Periods\Pages;

use App\AppPanel\Clusters\Finance\Resources\Periods\PeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePeriods extends ManageRecords
{
    protected static string $resource = PeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
