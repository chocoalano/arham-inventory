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
            ImportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-payment'))
                ->importer(PaymentImporter::class),
            ExportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-payment'))
                ->exporter(PaymentExporter::class)
        ];
    }
}
