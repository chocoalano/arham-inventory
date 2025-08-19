<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductVariants\Tables;

use App\Models\Inventory\ProductVariant;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class ProductVariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU Produk')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sku_variant')
                    ->label('SKU Varian')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('color')
                    ->label('Warna')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('size')
                    ->label('Ukuran')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('stocks')
                    ->label('Total Stok')
                    ->state(
                        fn(ProductVariant $record): int =>
                        (int) $record->stocks->sum('qty')
                    ),
                TextColumn::make('stock_list')
                    ->label('Stok per Gudang')
                    ->getStateUsing(function (ProductVariant $record) {
                        $stocks = $record->relationLoaded('stocks')
                            ? $record->stocks
                            : $record->stocks()->with('warehouse')->get();

                        if ($stocks->isEmpty()) {
                            return '-';
                        }

                        return $stocks->map(function ($stock) {
                            $warehouse = e($stock->warehouse?->name ?? '-');
                            $qty = (int) $stock->qty;
                            $style = $qty <= 0 ? 'style="color:#dc2626;"' : '';
                            return "<div {$style}>{$warehouse}: <strong>{$qty}</strong></div>";
                        })->implode('');
                    })
                    ->html()
                    ->sortable(false)
                    ->toggleable(),
            ])

            ->filters([
                // Filter berdasarkan Produk (pakai kolom 'sku' agar konsisten dengan kolom)
                SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'sku')
                    ->searchable()
                    ->preload(),

                // Filter warna (distinct dari DB)
                SelectFilter::make('color')
                    ->label('Warna')
                    ->options(fn() => ProductVariant::query()
                        ->whereNotNull('color')
                        ->where('color', '!=', '')
                        ->orderBy('color')
                        ->distinct()
                        ->pluck('color', 'color')
                        ->toArray()),

                // Filter ukuran (distinct dari DB)
                SelectFilter::make('size')
                    ->label('Ukuran')
                    ->options(fn() => ProductVariant::query()
                        ->whereNotNull('size')
                        ->where('size', '!=', '')
                        ->orderBy('size')
                        ->distinct()
                        ->pluck('size', 'size')
                        ->toArray()),

                // Ada stok (>0) ?
                TernaryFilter::make('has_stock')
                    ->label('Ada Stok')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn($query) => $query->whereHas(
                            'stocks',
                            fn($q) =>
                            $q->selectRaw('product_variant_id, SUM(qty) as total_qty')
                                ->groupBy('product_variant_id')
                                ->havingRaw('SUM(qty) > 0')
                        ),
                        false: fn($query) => $query->whereDoesntHave(
                            'stocks',
                            fn($q) =>
                            $q->selectRaw('product_variant_id, SUM(qty) as total_qty')
                                ->groupBy('product_variant_id')
                                ->havingRaw('SUM(qty) > 0')
                        ),
                    ),

                // Ada transaksi ?
                TernaryFilter::make('has_transactions')
                    ->label('Ada Transaksi')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn($query) => $query->whereHas('transactions'),
                        false: fn($query) => $query->whereDoesntHave('transactions'),
                    ),
            ])

            ->recordActions([
                // === EDIT ===
                EditAction::make()
                    ->label('Edit')
                    // Nonaktifkan tombol kalau sudah ada transaksi (UX jelas)
                    ->disabled(fn(ProductVariant $record) => $record->transactions()->exists())
                    ->tooltip(
                        fn(ProductVariant $record) =>
                        $record->transactions()->exists()
                        ? 'Varian ini sudah memiliki transaksi dan tidak bisa diedit.'
                        : null
                    )
                    // Validasi server-side saat submit
                    ->action(function (ProductVariant $record, array $data, EditAction $action) {
                        if ($record->transactions()->exists()) {
                            Notification::make()
                                ->title('Tidak bisa diedit')
                                ->body('Varian ini sudah memiliki transaksi.')
                                ->danger()
                                ->send();

                            $action->halt();
                            return;
                        }

                        $record->update($data);

                        Notification::make()
                            ->title('Varian diperbarui')
                            ->success()
                            ->send();
                    }),

                // === DELETE (row) ===
                DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->disabled(fn(ProductVariant $record) => $record->transactions()->exists())
                    ->tooltip(
                        fn(ProductVariant $record) =>
                        $record->transactions()->exists()
                        ? 'Varian ini sudah memiliki transaksi dan tidak bisa dihapus.'
                        : null
                    )
                    ->action(function (ProductVariant $record, DeleteAction $action) {
                        if ($record->transactions()->exists()) {
                            Notification::make()
                                ->title('Tidak bisa dihapus')
                                ->body('Varian ini sudah memiliki transaksi.')
                                ->danger()
                                ->send();

                            $action->halt();
                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Varian dihapus')
                            ->success()
                            ->send();
                    }),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    // === BULK DELETE ===
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            // Pisahkan yang terblokir dan yang aman dihapus
                            $blocked = $records->filter(
                                fn(ProductVariant $r) => $r->transactions()->exists()
                            );
                            $deletable = $records->reject(
                                fn(ProductVariant $r) => $r->transactions()->exists()
                            );

                            // Hapus yang aman
                            if ($deletable->isNotEmpty()) {
                                $deletable->each->delete();

                                Notification::make()
                                    ->title('Varian tanpa transaksi dihapus')
                                    ->success()
                                    ->send();
                            }

                            // Beri tahu yang tidak dihapus
                            if ($blocked->isNotEmpty()) {
                                $list = $blocked->map(function (ProductVariant $r) {
                                    // Sesuaikan field identitas varian agar informatif
                                    return $r->sku_variant ?? $r->id;
                                })->join(', ');

                                Notification::make()
                                    ->title('Sebagian tidak dihapus')
                                    ->body('Varian berikut tidak dihapus karena memiliki transaksi: ' . $list)
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
