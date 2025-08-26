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
            ImportAction::make()->importer(InvoiceImporter::class),
            ExportAction::make()->exporter(InvoiceExporter::class)
        ];
    }
}
