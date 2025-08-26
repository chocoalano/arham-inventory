<?php

namespace App\AppPanel\Clusters\Produk\Resources\Suppliers\Pages;

use App\AppPanel\Clusters\Produk\Resources\Suppliers\SupplierResource;
use App\Filament\Imports\SupplierImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSuppliers extends ManageRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()->importer(SupplierImporter::class)
        ];
    }
}
