<?php

namespace App\AppPanel\Clusters\Produk\Resources\Products\Pages;

use App\AppPanel\Clusters\Produk\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
