<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductVariants\Tables;

use App\Models\Inventory\ProductVariant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductVariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.sku'),
                TextColumn::make('sku_variant'),
                TextColumn::make('color'),
                TextColumn::make('size'),
                TextColumn::make('stocks')
                    ->label('Total Stok')
                    ->state(
                        fn(ProductVariant $record): int =>
                        $record->stocks->sum('qty')
                    ),
                TextColumn::make('stock_list')
                    ->label('Stok per Gudang')
                    ->getStateUsing(function (ProductVariant $record) {
                        // jika sudah di-eager load gunakan collection, kalau belum ambil relasi termasuk warehouse
                        $stocks = $record->relationLoaded('stocks')
                            ? $record->stocks
                            : $record->stocks()->with('warehouse')->get();

                        if ($stocks->isEmpty()) {
                            return '-';
                        }

                        // kembalikan HTML dengan <div> per baris; e(...) untuk escape nama gudang
                        return $stocks
                            ->map(function ($stock) {
                            $warehouse = e($stock->warehouse?->name ?? '-');
                            $qty = (int) $stock->qty;

                            // opsional: beri warna jika qty 0
                            $style = $qty <= 0 ? 'style="color:#dc2626;"' : '';

                            return "<div {$style}>{$warehouse}: <strong>{$qty}</strong></div>";
                        })
                            ->implode(''); // sudah berisi <div>...</div><div>...</div> jadi jadi multi-line
                    })
                    ->html()        // penting agar HTML tidak di-escape
                    ->sortable(false)
                    ->toggleable()
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
