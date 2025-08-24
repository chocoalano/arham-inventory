<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Warehouses\Pages;
use App\AppPanel\Clusters\Inventory\Resources\Warehouses\WarehouseResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListWarehouseActivities extends ListActivities
{
    protected static string $resource = WarehouseResource::class;
}
