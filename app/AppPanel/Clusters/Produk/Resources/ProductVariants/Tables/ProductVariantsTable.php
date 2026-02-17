<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductVariants\Tables;

use App\AppPanel\Clusters\Produk\Resources\ProductVariants\ProductVariantResource;
use App\Models\Inventory\ProductVariant;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ProductVariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Hemat query: eager-load relasi & total stok
            ->modifyQueryUsing(
                fn($query) => $query
                    ->with(['product:id,sku', 'stocks.warehouse'])
                    ->withSum('stocks as total_stock', 'qty')
            )

            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU Produk')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('product.category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Nama Produk')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('sku_variant')
                    ->label('SKU Varian')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Varian')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('raw_material.name')
                    ->label('Jenis Bahan Baku')
                    ->sortable()
                    ->searchable(true),

                TextColumn::make('color')
                    ->label('Warna')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('size')
                    ->label('Ukuran')
                    ->sortable()
                    ->searchable(),

                // Gunakan alias total_stock dari withSum()
                TextColumn::make('total_stock')
                    ->label('Total Stok')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('stock_list')
                    ->label('Stok per Gudang')
                    ->state(function (ProductVariant $record) {
                        $stocks = $record->stocks;

                        if ($stocks->isEmpty()) {
                            return '-';
                        }

                        // Batasi jumlah stok yang ditampilkan, misal 5
                        $limitedStocks = $stocks->take(5);

                        $output = $limitedStocks->map(function ($stock) {
                            $warehouse = e($stock->warehouse?->name ?? '-');
                            $qty = (int) $stock->qty;
                            $style = $qty <= 0 ? 'style="color:#dc2626;"' : '';
                            return "<div {$style}>{$warehouse}: <strong>{$qty}</strong></div>";
                        })->implode('');

                        // Jika ada stok yang tidak ditampilkan, tambahkan indikator
                        if ($stocks->count() > $limitedStocks->count()) {
                            $output .= '<div style="color:#6b7280;">...dan lainnya</div>';
                        }

                        return $output;
                    })
                    ->limit(5)
                    ->html()
                    ->sortable(false)
                    ->toggleable(),
            ])

            ->filters([
                SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'sku')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('size')
                    ->label('Ukuran')
                    ->options(fn() => array_combine(ProductVariant::SIZES, ProductVariant::SIZES))
                    ->searchable()
                    ->preload(),

                // Cukup cek ada baris stok dengan qty > 0 (lebih murah daripada SUM di filter)
                TernaryFilter::make('has_stock')
                    ->label('Ada Stok')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn($q) => $q->whereHas('stocks', fn($s) => $s->where('qty', '>', 0)),
                        false: fn($q) => $q->whereDoesntHave('stocks', fn($s) => $s->where('qty', '>', 0)),
                    ),

                TernaryFilter::make('has_transactions')
                    ->label('Ada Transaksi')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn($q) => $q->whereHas('transactions'),
                        false: fn($q) => $q->whereDoesntHave('transactions'),
                    ),

                TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)

            ->recordActions([
                ActionGroup::make([
                    Action::make('activities')
                        ->label('Aktivitas')
                        ->icon('heroicon-m-clock')
                        ->color('primary')
                        ->visible(fn(): bool => (bool) auth()->user()?->hasRole('Superadmin'))
                        ->url(fn($record) => ProductVariantResource::getUrl('activities', ['record' => $record])),

                    EditAction::make()
                        ->label('Edit')
                        ->disabled(fn(ProductVariant $r) => self::blockedByTransactions($r))
                        ->tooltip(fn(ProductVariant $r) => self::blockedByTransactions($r)
                            ? 'Varian ini sudah memiliki transaksi dan tidak bisa diedit.'
                            : null)
                        ->action(function (ProductVariant $record, array $data, EditAction $action) {
                            if (self::blockedByTransactions($record)) {
                                self::notify('Tidak bisa diedit', 'Varian ini sudah memiliki transaksi.', true);
                                $action->halt();
                                return;
                            }

                            $record->update($data);
                            self::notify('Varian diperbarui');
                        }),

                    DeleteAction::make()
                        ->label('Hapus')
                        ->requiresConfirmation()
                        ->disabled(fn(ProductVariant $r) => self::blockedByTransactions($r))
                        ->tooltip(fn(ProductVariant $r) => self::blockedByTransactions($r)
                            ? 'Varian ini sudah memiliki transaksi dan tidak bisa dihapus.'
                            : null)
                        ->action(function (ProductVariant $record, DeleteAction $action) {
                            if (self::blockedByTransactions($record)) {
                                self::notify('Tidak bisa dihapus', 'Varian ini sudah memiliki transaksi.', true);
                                $action->halt();
                                return;
                            }

                            $record->delete();
                            self::notify('Varian dihapus');
                        }),

                    RestoreAction::make(),

                    ReplicateAction::make('replicate')
                        ->label('Duplikasi')
                        ->mutateRecordDataUsing(function (array $data): array {
                            $data['sku_variant'] = ProductVariant::generateUniqueSkuVariant(
                                productSku: $data['product']['sku'] ?? null,
                                color: $data['color'] ?? null,
                                size: $data['size'] ?? null,
                            );
                            unset($data['deleted_at']);

                            return $data;
                        })
                        ->form([
                            Hidden::make('product_id')
                                ->default(fn($record) => $record?->product_id)
                                ->dehydrated(),

                            Section::make()
                                ->schema([
                                    TextInput::make('sku_variant')
                                        ->label('SKU Varian')
                                        ->required()
                                        ->maxLength(64)
                                        ->default(fn(array $data) => ProductVariant::generateUniqueSkuVariant(
                                            productSku: $data['product']['sku'] ?? null,
                                            color: $data['color'] ?? null,
                                            size: $data['size'] ?? null,
                                        ))
                                        ->unique(table: ProductVariant::class, column: 'sku_variant'),

                                    TextInput::make('color')
                                        ->label('Warna')
                                        ->maxLength(50)
                                        ->live(onBlur: true),

                                    Select::make('size')
                                        ->label('Ukuran')
                                        ->options(fn() => array_combine(ProductVariant::SIZES, ProductVariant::SIZES))
                                        ->required()
                                        ->searchable()
                                        ->rules(fn(Get $get) => [
                                            Rule::in(ProductVariant::SIZES),
                                            Rule::unique('product_variants', 'size')->where(
                                                fn($q) => $q
                                                    ->where('product_id', $get('product_id'))
                                                    ->where('color', $get('color'))
                                                    ->whereNull('deleted_at')
                                            ),
                                        ])
                                        ->validationMessages([
                                            'in' => 'Ukuran tidak valid. Pilih salah satu: ' . implode(', ', ProductVariant::SIZES),
                                            'unique' => 'Kombinasi Produk + Warna + Ukuran sudah dipakai.',
                                        ]),
                                ])->columns(2),
                        ]),

                    ForceDeleteAction::make(),
                ]),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            [$blocked, $deletable] = $records->partition(
                                fn(ProductVariant $r) => self::blockedByTransactions($r)
                            );

                            if ($deletable->isNotEmpty()) {
                                $deletable->each->delete();
                                self::notify('Varian tanpa transaksi dihapus');
                            }

                            if ($blocked->isNotEmpty()) {
                                $list = $blocked
                                    ->map(fn(ProductVariant $r) => $r->sku_variant ?? $r->id)
                                    ->join(', ');

                                self::notify(
                                    'Sebagian tidak dihapus',
                                    'Varian berikut tidak dihapus karena memiliki transaksi: ' . $list,
                                    true
                                );
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /** Helper: cek blokir karena ada transaksi */
    protected static function blockedByTransactions(ProductVariant $record): bool
    {
        return $record->transactions()->exists();
    }

    /** Helper: notifikasi ringkas */
    protected static function notify(string $title, ?string $body = null, bool $danger = false): void
    {
        Notification::make()
                    ->title($title)
                    ->when($body, fn($n) => $n->body($body))
            ->{$danger ? 'danger' : 'success'}()
                ->send();
    }
}
