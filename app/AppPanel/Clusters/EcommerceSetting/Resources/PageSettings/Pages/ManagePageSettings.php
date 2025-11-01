<?php

namespace App\AppPanel\Clusters\EcommerceSetting\Resources\PageSettings\Pages;

use App\AppPanel\Clusters\EcommerceSetting\Resources\PageSettings\PageSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePageSettings extends ManageRecords
{
    protected static string $resource = PageSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
