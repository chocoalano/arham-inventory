<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductVariants\Pages;

use App\AppPanel\Clusters\Produk\Resources\ProductVariants\ProductVariantResource;
use App\Filament\Exports\ProductVariantExporter;
use App\Filament\Imports\ProductVariantImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListProductVariants extends ListRecords
{
    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('import-product_variant'))
                ->importer(ProductVariantImporter::class),
            ExportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-product_variant'))
                ->exporter(ProductVariantExporter::class)
        ];
    }
}
