<?php

namespace App\AppPanel\Clusters\FinanceReports\Resources\ProfitAndLosses\Pages;

use App\AppPanel\Clusters\FinanceReports\Resources\ProfitAndLosses\ProfitAndLossesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProfitAndLosses extends ManageRecords
{
    protected static string $resource = ProfitAndLossesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
