<?php

namespace App\AppPanel\Clusters\FinanceReports\Resources\GeneralLedgers\Pages;

use App\AppPanel\Clusters\FinanceReports\Resources\GeneralLedgers\GeneralLedgerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGeneralLedgers extends ManageRecords
{
    protected static string $resource = GeneralLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
