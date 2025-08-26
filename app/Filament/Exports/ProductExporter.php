<?php

namespace App\Filament\Exports;

use App\Models\Inventory\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            // Identitas & pemasok
            ExportColumn::make('sku')->label('SKU'),
            ExportColumn::make('name')->label('Name'),
            ExportColumn::make('model')->label('Model'),
            ExportColumn::make('brand')->label('Brand'),
            ExportColumn::make('supplier_id')->label('Supplier ID'),
            ExportColumn::make('supplier.name')->label('Supplier Name'),

            // Status
            ExportColumn::make('is_active')
                ->label('Active')
                ->formatStateUsing(fn($v) => $v ? 'Yes' : 'No'),

            // Deskripsi (dipersingkat agar file portable)
            ExportColumn::make('description')
                ->label('Description')
                ->formatStateUsing(function ($v) {
                    if ($v === null)
                        return null;
                    $s = trim((string) $v);
                    return mb_strlen($s) > 500 ? mb_substr($s, 0, 500) . '…' : $s;
                }),

            // Ringkasan relasi (disarankan eager-count di query pemanggil)
            ExportColumn::make('variants_count')
                ->label('Variants Count')
                ->state(fn(Product $r) => $r->variants_count ?? $r->variants()->count()),

            ExportColumn::make('images_count')
                ->label('Images Count')
                ->state(fn(Product $r) => $r->images_count ?? $r->images()->count()),

            // Opsional: total on-hand di warehouse user (gunakan accessor helper di model)
            // Catatan: ini melakukan query per baris jika tidak di-cache/di-eager-load.
            ExportColumn::make('onhand_user_warehouse')
                ->label('On-hand (User Warehouse)')
                ->state(function (Product $r) {
                    // Mengandalkan helper onHandInWarehouse() yang ada di model
                    // Fallback mengambil warehouse_id dari request() atau user, sesuai model.
                    try {
                        return $r->onHandInWarehouse();
                    } catch (\Throwable $e) {
                        return null; // jangan gagalkan export bila gagal hitung
                    }
                }),

            // Timestamps
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
        $body = 'Your product export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
