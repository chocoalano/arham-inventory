<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Transactions\Pages;

use App\AppPanel\Clusters\Inventory\Resources\Transactions\TransactionResource;
use App\Filament\Exports\TransactionExporter;
use App\Filament\Imports\TransactionImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->exporter(TransactionExporter::class)
                ->columnMappingColumns(3),
            ImportAction::make()
                ->importer(TransactionImporter::class)
        ];
    }
}
