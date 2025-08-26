<?php

namespace App\Filament\Exports;

use App\Models\Inventory\ProductVariant;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ProductVariantExporter extends Exporter
{
    protected static ?string $model = ProductVariant::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            // Produk induk
            ExportColumn::make('product_id')->label('Product ID'),
            ExportColumn::make('product.sku')->label('Product SKU'),
            ExportColumn::make('product.name')->label('Product Name'),

            // Identitas varian
            ExportColumn::make('sku_variant')->label('SKU Variant'),
            ExportColumn::make('barcode')->label('Barcode'),
            ExportColumn::make('color')->label('Color'),
            ExportColumn::make('size')->label('Size'),

            // Harga (2 desimal)
            ExportColumn::make('cost_price')
                ->label('Cost Price')
                ->formatStateUsing(fn($v) => is_null($v) ? null : number_format((float) $v, 2, '.', '')),
            ExportColumn::make('price')
                ->label('Price')
                ->formatStateUsing(fn($v) => is_null($v) ? null : number_format((float) $v, 2, '.', '')),

            ExportColumn::make('status')->label('Status'),

            // Stok – dua opsi:
            // 1) Jika kamu pakai modifyQueryUsing withSum/withAggregate (disarankan), kolom ini baca alias:
            ExportColumn::make('stocks_sum_qty')
                ->label('Total Stock (All WH)')
                ->state(fn(ProductVariant $r) => $r->stocks_sum_qty ?? $r->total_stock ?? $r->stocks()->sum('qty')),

            ExportColumn::make('stocks_onhand_sum')
                ->label('On Hand (All WH)')
                ->state(function (ProductVariant $r) {
                    // Jika alias tersedia (lihat modifyQueryUsing di bawah), gunakan itu.
                    if (isset($r->stocks_onhand_sum)) {
                        return (int) $r->stocks_onhand_sum;
                    }
                    // fallback ke accessor (akan query per baris)
                    return $r->stock_on_hand ?? (int) $r->stocks()->sum(\DB::raw('qty - reserved_qty'));
                }),

            // Timestamp
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
        $body = 'Your product variant export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
