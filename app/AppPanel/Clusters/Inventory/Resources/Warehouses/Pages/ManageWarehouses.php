<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Warehouses\Pages;

use App\AppPanel\Clusters\Inventory\Resources\Warehouses\WarehouseResource;
use App\Filament\Exports\WarehouseExporter;
use App\Filament\Imports\WarehouseImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageWarehouses extends ManageRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Model {
                    return $model::create($data);
                }),
            ImportAction::make()->importer(WarehouseImporter::class),
            ExportAction::make()->exporter(WarehouseExporter::class)
        ];
    }
}
