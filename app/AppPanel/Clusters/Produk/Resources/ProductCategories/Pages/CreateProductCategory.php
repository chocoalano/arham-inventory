<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductCategories\Pages;

use App\AppPanel\Clusters\Produk\Resources\ProductCategories\ProductCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;
}
