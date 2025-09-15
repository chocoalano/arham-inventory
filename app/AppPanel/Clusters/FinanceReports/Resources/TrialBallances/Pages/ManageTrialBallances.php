<?php

namespace App\AppPanel\Clusters\FinanceReports\Resources\TrialBallances\Pages;

use App\AppPanel\Clusters\FinanceReports\Resources\TrialBallances\TrialBallanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTrialBallances extends ManageRecords
{
    protected static string $resource = TrialBallanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
