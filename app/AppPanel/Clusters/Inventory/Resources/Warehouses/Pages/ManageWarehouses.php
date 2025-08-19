<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Warehouses\Pages;

use App\AppPanel\Clusters\Inventory\Resources\Warehouses\WarehouseResource;
use Filament\Actions\CreateAction;
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
        ];
    }
}
