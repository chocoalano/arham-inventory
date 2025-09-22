<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialCategories\Pages;

use App\AppPanel\Clusters\Produksi\Resources\RawMaterialCategories\RawMaterialCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRawMaterialCategories extends ManageRecords
{
    protected static string $resource = RawMaterialCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
