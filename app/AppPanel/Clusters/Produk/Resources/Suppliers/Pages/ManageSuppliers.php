<?php

namespace App\AppPanel\Clusters\Produk\Resources\Suppliers\Pages;

use App\AppPanel\Clusters\Produk\Resources\Suppliers\SupplierResource;
use App\Filament\Exports\SupplierExporter;
use App\Filament\Imports\SupplierImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSuppliers extends ManageRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-supplier'))
                ->importer(SupplierImporter::class),
            ExportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-supplier'))
                ->exporter(SupplierExporter::class)
        ];
    }
}
