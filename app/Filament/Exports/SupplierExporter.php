<?php

namespace App\Filament\Exports;

use App\Models\Inventory\Supplier;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class SupplierExporter extends Exporter
{
    protected static ?string $model = Supplier::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('code')->label('Code'),
            ExportColumn::make('name')->label('Name'),
            ExportColumn::make('contact_name')->label('Contact Name'),
            ExportColumn::make('phone')->label('Phone'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('address')->label('Address'),
            ExportColumn::make('district')->label('District'),
            ExportColumn::make('city')->label('City'),
            ExportColumn::make('province')->label('Province'),
            ExportColumn::make('postal_code')->label('Postal Code'),

            ExportColumn::make('lat')
                ->label('Lat')
                ->formatStateUsing(fn($v) => is_null($v) ? null : number_format((float) $v, 7, '.', '')),
            ExportColumn::make('lng')
                ->label('Lng')
                ->formatStateUsing(fn($v) => is_null($v) ? null : number_format((float) $v, 7, '.', '')),

            ExportColumn::make('is_active')
                ->label('Active')
                ->formatStateUsing(fn($v) => $v ? 'Yes' : 'No'),

            // (Opsional) ringkasan relasi – gunakan withCount agar anti N+1
            ExportColumn::make('products_count')->label('Products Count')
                ->state(fn(Supplier $r) => $r->products_count ?? $r->products()->count()),

            ExportColumn::make('transactions_count')->label('Transactions Count')
                ->state(fn(Supplier $r) => $r->transactions_count ?? $r->transactions()->count()),

            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
            ExportColumn::make('deleted_at')
                ->label('Deleted At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your supplier export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
