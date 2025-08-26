<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference_number')
                ->label('Nomor Referensi'),

            ExportColumn::make('type')
                ->label('Jenis Transaksi'),

            ExportColumn::make('transaction_date')
                ->label('Tanggal Transaksi')
                ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i')),

            ExportColumn::make('sourceWarehouse.name')
                ->label('Gudang Asal'),

            ExportColumn::make('destinationWarehouse.name')
                ->label('Gudang Tujuan'),

            ExportColumn::make('customer_name')
                ->label('Nama Pelanggan'),

            ExportColumn::make('customer_phone')
                ->label('Telepon Pelanggan'),

            ExportColumn::make('customer_full_address')
                ->label('Alamat Pelanggan'),

            ExportColumn::make('item_count')
                ->label('Jumlah Item'),

            ExportColumn::make('grand_total')
                ->label('Total Transaksi')
                ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 0, ',', '.') : null),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn($state) => match ($state) {
                    'draft' => 'Draft',
                    'posted' => 'Diposting',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst($state),
                }),

            ExportColumn::make('posted_at')
                ->label('Tanggal Posting')
                ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i')),

            ExportColumn::make('creator.name')
                ->label('Dibuat Oleh'),

            ExportColumn::make('remarks')
                ->label('Catatan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
