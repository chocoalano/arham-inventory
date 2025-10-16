<?php

namespace App\AppPanel\Clusters\Produksi\Resources\ProductBoms\Pages;

use App\AppPanel\Clusters\Produksi\Resources\ProductBoms\ProductBomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProductBoms extends ManageRecords
{
    protected static string $resource = ProductBomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth('5xl'),
        ];
    }
}
