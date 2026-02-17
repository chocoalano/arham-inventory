<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductCategories\Pages;

use App\AppPanel\Clusters\Produk\Resources\ProductCategories\ProductCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductCategory extends ViewRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
