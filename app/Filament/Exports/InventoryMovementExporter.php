<?php

namespace App\Filament\Exports;

use App\Models\Inventory\InventoryMovement;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class InventoryMovementExporter extends Exporter
{
    protected static ?string $model = InventoryMovement::class;

    public static function getColumns(): array
    {
        return [
            // IDs langsung
            ExportColumn::make('id')->label('ID'),

            ExportColumn::make('transaction_id')->label('Transaction ID'),

            // Warehouse: tampilkan ID dan CODE agar mudah cross-check
            ExportColumn::make('from_warehouse_id')->label('From Warehouse ID'),
            ExportColumn::make('from_warehouse.code')->label('From Warehouse Code'),

            ExportColumn::make('to_warehouse_id')->label('To Warehouse ID'),
            ExportColumn::make('to_warehouse.code')->label('To Warehouse Code'),

            // Variant
            ExportColumn::make('product_variant_id')->label('Product Variant ID'),
            ExportColumn::make('variant.sku_variant')->label('Product Variant SKU'),

            // Payload
            ExportColumn::make('qty_change')->label('Qty Change'),
            ExportColumn::make('type')->label('Type'),

            ExportColumn::make('occurred_at')
                ->label('Occurred At')
                ->formatStateUsing(fn ($dt) => optional($dt)->format('Y-m-d H:i:s')),

            ExportColumn::make('remarks')->label('Remarks'),

            // Creator meta
            ExportColumn::make('created_by')->label('Created By ID'),
            ExportColumn::make('creator.email')->label('Created By Email'),
            ExportColumn::make('creator.name')->label('Created By Name'),

            // Timestamps
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
            ExportColumn::make('deleted_at')
                ->label('Deleted At'),
        ];
    }

    // v4: instance method
    public function getFileName(Export $export): string
    {
        return 'inventory_movements_' . $export->getKey() . '_' . now()->format('Ymd_His');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your inventory movement export has completed and '
            . Number::format($export->successful_rows) . ' '
            . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    /**
     * (Opsional) Kalau ingin cegah N+1 saat export banyak baris.
     * Panggil Exporter lewat query yang sudah eager-load relasi:
     * InventoryMovement::with(['from_warehouse','to_warehouse','variant','creator','transaction'])
     */
}
