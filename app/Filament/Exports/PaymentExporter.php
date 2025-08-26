<?php

namespace App\Filament\Exports;

use App\Models\Inventory\Payment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('invoice_id')->label('Invoice ID'),
            ExportColumn::make('invoice.invoice_number')->label('Invoice Number'),

            ExportColumn::make('amount')
                ->label('Amount')
                ->formatStateUsing(fn ($v) => is_null($v) ? null : number_format((float)$v, 2, '.', '')),

            ExportColumn::make('method')->label('Method'),
            ExportColumn::make('reference_no')->label('Reference No'),

            ExportColumn::make('paid_at')
                ->label('Paid At')
                ->formatStateUsing(fn ($dt) => optional($dt)->format('Y-m-d H:i:s')),

            ExportColumn::make('notes')->label('Notes'),

            ExportColumn::make('received_by')->label('Receiver ID'),
            ExportColumn::make('receiver.email')->label('Receiver Email'),
            ExportColumn::make('receiver.name')->label('Receiver Name'),

            ExportColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($dt) => optional($dt)->format('Y-m-d H:i:s')),
            ExportColumn::make('updated_at')
                ->label('Updated At')
                ->formatStateUsing(fn ($dt) => optional($dt)->format('Y-m-d H:i:s')),
            ExportColumn::make('deleted_at')
                ->label('Deleted At')
                ->formatStateUsing(fn ($dt) => optional($dt)->format('Y-m-d H:i:s')),
        ];
    }

    // v4: method instance
    public function getFileName(Export $export): string
    {
        return 'payments_' . $export->getKey() . '_' . now()->format('Ymd_His');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your payment export has completed and '
              . Number::format($export->successful_rows) . ' '
              . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                   . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    /**
     * Untuk performa, panggil exporter via query eager-load:
     * Payment::with(['invoice', 'receiver'])
     */
}
