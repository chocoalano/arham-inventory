<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductVariants\Pages;

use App\AppPanel\Clusters\Produk\Resources\ProductVariants\ProductVariantResource;
use App\Filament\Imports\ProductVariantImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListProductVariants extends ListRecords
{
    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()->importer(ProductVariantImporter::class)
        ];
    }
}
