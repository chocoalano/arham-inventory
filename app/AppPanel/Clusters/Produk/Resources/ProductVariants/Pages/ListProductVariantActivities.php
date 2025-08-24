<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductVariants\Pages;
use App\AppPanel\Clusters\Produk\Resources\ProductVariants\ProductVariantResource;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListProductVariantActivities extends ListActivities
{
    protected static string $resource = ProductVariantResource::class;
}
