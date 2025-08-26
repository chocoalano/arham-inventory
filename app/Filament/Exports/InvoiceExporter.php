<?php

namespace App\Filament\Exports;

use App\Models\Inventory\Invoice;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class InvoiceExporter extends Exporter
{
    protected static ?string $model = Invoice::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('invoice_number')->label('Invoice Number'),
            ExportColumn::make('transaction_id')->label('Transaction ID'),

            ExportColumn::make('issued_at')
                ->label('Issued At')
                ->formatStateUsing(fn ($dt) => optional($dt)->format('Y-m-d H:i:s')),
            ExportColumn::make('due_at')
                ->label('Due At')
                ->formatStateUsing(fn ($dt) => optional($dt)->format('Y-m-d H:i:s')),

            // angka: konsisten 2 desimal
            ExportColumn::make('subtotal')->label('Subtotal')
                ->formatStateUsing(fn ($v) => is_null($v) ? null : number_format((float)$v, 2, '.', '')),
            ExportColumn::make('discount_total')->label('Discount Total')
                ->formatStateUsing(fn ($v) => is_null($v) ? null : number_format((float)$v, 2, '.', '')),
            ExportColumn::make('tax_total')->label('Tax Total')
                ->formatStateUsing(fn ($v) => is_null($v) ? null : number_format((float)$v, 2, '.', '')),
            ExportColumn::make('shipping_fee')->label('Shipping Fee')
                ->formatStateUsing(fn ($v) => is_null($v) ? null : number_format((float)$v, 2, '.', '')),
            ExportColumn::make('total_amount')->label('Total Amount')
                ->formatStateUsing(fn ($v) => is_null($v) ? null : number_format((float)$v, 2, '.', '')),
            ExportColumn::make('paid_amount')->label('Paid Amount')
                ->formatStateUsing(fn ($v) => is_null($v) ? null : number_format((float)$v, 2, '.', '')),

            ExportColumn::make('is_paid')->label('Paid')
                ->formatStateUsing(fn ($v) => $v ? 'Yes' : 'No'),

            // Outstanding (accessor di model)
            ExportColumn::make('outstanding')->label('Outstanding'),

            // Metadata
            ExportColumn::make('created_at')->label('Created At'),
            ExportColumn::make('updated_at')->label('Updated At'),
            ExportColumn::make('deleted_at')->label('Deleted At'),
        ];
    }

    // v4: instance method
    public function getFileName(Export $export): string
    {
        return 'invoices_' . $export->getKey() . '_' . now()->format('Ymd_His');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your invoice export has completed and '
              . Number::format($export->successful_rows) . ' '
              . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                   . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    /**
     * (Opsional) Hindari N+1 saat export besar: panggil exporter
     * lewat query yang sudah eager load relasi:
     * Invoice::with(['transaction', 'payments', 'payments.receiver'])
     */
}
