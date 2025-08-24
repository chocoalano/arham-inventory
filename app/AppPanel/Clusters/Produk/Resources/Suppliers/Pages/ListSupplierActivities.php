<?php

namespace App\AppPanel\Clusters\Produk\Resources\Suppliers\Pages;
use App\AppPanel\Clusters\Produk\Resources\Suppliers\SupplierResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListSupplierActivities extends ListActivities
{
    protected static string $resource = SupplierResource::class;
}
