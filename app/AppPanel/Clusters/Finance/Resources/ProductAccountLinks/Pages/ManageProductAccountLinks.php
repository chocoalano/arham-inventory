<?php

namespace App\AppPanel\Clusters\Finance\Resources\ProductAccountLinks\Pages;

use App\AppPanel\Clusters\Finance\Resources\ProductAccountLinks\ProductAccountLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProductAccountLinks extends ManageRecords
{
    protected static string $resource = ProductAccountLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
