<?php

namespace App\Filament\Exports;

use App\Models\Inventory\Warehouse;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class WarehouseExporter extends Exporter
{
    protected static ?string $model = Warehouse::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')->label('Code'),
            ExportColumn::make('name')->label('Name'),
            ExportColumn::make('address')->label('Address'),
            ExportColumn::make('district')->label('District'),
            ExportColumn::make('city')->label('City'),
            ExportColumn::make('province')->label('Province'),
            ExportColumn::make('postal_code')->label('Postal Code'),
            ExportColumn::make('lat')
                ->label('Lat')
                ->formatStateUsing(fn ($v) => is_null($v) ? null : number_format((float) $v, 7, '.', '')),
            ExportColumn::make('lng')
                ->label('Lng')
                ->formatStateUsing(fn ($v) => is_null($v) ? null : number_format((float) $v, 7, '.', '')),
            ExportColumn::make('phone')->label('Phone'),
            ExportColumn::make('is_active')
                ->label('Active')
                ->formatStateUsing(fn ($v) => $v ? 'Yes' : 'No'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
            ExportColumn::make('deleted_at')
                ->label('Deleted At'),
        ];
    }

    // ⬇️ v4: method instance, bukan static
    public function getFileName(Export $export): string
    {
        return 'warehouses_' . $export->getKey() . '_' . now()->format('Ymd_His');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your warehouse export has completed and '
            . Number::format($export->successful_rows) . ' '
            . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
