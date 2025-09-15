<?php

namespace App\AppPanel\Clusters\Finance\Resources\FiscalYears\Pages;

use App\AppPanel\Clusters\Finance\Resources\FiscalYears\FiscalYearResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFiscalYears extends ManageRecords
{
    protected static string $resource = FiscalYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
