<?php

namespace App\AppPanel\Clusters\Produk\Resources\Products\Pages;

use App\AppPanel\Clusters\Produk\Resources\Products\ProductResource;
use App\Filament\Imports\ProductImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->importer(ProductImporter::class)
        ];
    }
}
