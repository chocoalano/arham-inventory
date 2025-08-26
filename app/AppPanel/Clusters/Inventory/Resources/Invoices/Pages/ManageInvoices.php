<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Invoices\Pages;

use App\AppPanel\Clusters\Inventory\Resources\Invoices\InvoiceResource;
use App\Filament\Exports\InvoiceExporter;
use App\Filament\Imports\InvoiceImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInvoices extends ManageRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-invoice'))
                ->importer(InvoiceImporter::class),
            ExportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-invoice'))
                ->exporter(InvoiceExporter::class)
        ];
    }
}
