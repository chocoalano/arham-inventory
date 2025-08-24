<?php

namespace App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Pages;
use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\InventoryMovementResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ManageInventoryActivities extends ListActivities
{
    protected static string $resource = InventoryMovementResource::class;
}
