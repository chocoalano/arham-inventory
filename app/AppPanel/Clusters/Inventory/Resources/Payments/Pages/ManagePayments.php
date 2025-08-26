<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Payments\Pages;

use App\AppPanel\Clusters\Inventory\Resources\Payments\PaymentResource;
use App\Filament\Exports\PaymentExporter;
use App\Filament\Imports\PaymentImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()->importer(PaymentImporter::class),
            ExportAction::make()->exporter(PaymentExporter::class)
        ];
    }
}
