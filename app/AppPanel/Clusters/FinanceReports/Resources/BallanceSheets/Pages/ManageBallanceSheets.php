<?php

namespace App\AppPanel\Clusters\FinanceReports\Resources\BallanceSheets\Pages;

use App\AppPanel\Clusters\FinanceReports\Resources\BallanceSheets\BallanceSheetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBallanceSheets extends ManageRecords
{
    protected static string $resource = BallanceSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
