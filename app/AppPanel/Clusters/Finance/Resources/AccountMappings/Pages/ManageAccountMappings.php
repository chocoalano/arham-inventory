<?php

namespace App\AppPanel\Clusters\Finance\Resources\AccountMappings\Pages;

use App\AppPanel\Clusters\Finance\Resources\AccountMappings\AccountMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAccountMappings extends ManageRecords
{
    protected static string $resource = AccountMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
