<?php

namespace App\AppPanel\Clusters\Produk\Resources\Products\Pages;
use App\AppPanel\Clusters\Produk\Resources\Products\ProductResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListProductActivities extends ListActivities
{
    protected static string $resource = ProductResource::class;
}
