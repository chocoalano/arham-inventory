<?php

namespace App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Pages;

use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\InventoryMovementResource;
use App\Filament\Exports\InventoryMovementExporter;
use App\Filament\Imports\InventoryMovementImporter;
use App\Models\Ecommerce\Product;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\WarehouseVariantStock;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageInventoryMovements extends ManageRecords
{
    protected static string $resource = InventoryMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): Model {
                    DB::beginTransaction();
                    try {
                        $qty = (int) abs($data['qty_change'] ?? $data['qty'] ?? 0);
                        $sourceId = (int) ($data['source_warehouse_id'] ?? 0);
                        $destId = (int) ($data['destination_warehouse_id'] ?? 0);
                        $variantId = (int) ($data['product_variant_id'] ?? 0);
                        $occurred = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();
                        $remarks = $data['remarks'] ?? null;

                        // --- Validasi dasar
                        if ($qty <= 0) {
                            throw ValidationException::withMessages([
                                'qty_change' => 'Qty harus lebih dari 0.'
                            ]);
                        }
                        if ($sourceId <= 0) {
                            throw ValidationException::withMessages([
                                'source_warehouse_id' => 'Gudang sumber wajib diisi.'
                            ]);
                        }
                        if ($destId < 0 || $sourceId === $destId) {
                            throw ValidationException::withMessages([
                                'destination_warehouse_id' => 'Gudang tujuan tidak valid atau sama dengan gudang sumber.'
                            ]);
                        }
                        if (! ProductVariant::query()->whereKey($variantId)->exists()) {
                            throw ValidationException::withMessages([
                                'product_variant_id' => 'Varian tidak valid.'
                            ]);
                        }
                        if ($destId === 0 && $sourceId !== 0) {
                            $trx = Transaction::create([
                                'type' => 'pemindahan',
                                'transaction_date' => $occurred,
                                'source_warehouse_id' => $sourceId,
                                'posted_at' => now(),
                                'created_by' => auth()->id(),
                                'remarks' => $remarks,
                            ]);

                            $source = WarehouseVariantStock::query()
                                ->where('warehouse_id', $sourceId)
                                ->where('product_variant_id', $variantId)
                                ->lockForUpdate()
                                ->first();

                            if (! $source || $source->qty < $qty) {
                                throw ValidationException::withMessages([
                                    'qty_change' => 'Stok tidak mencukupi di gudang sumber.'
                                ]);
                            }
                            $source->decrement('qty', $qty);

                            // Ambil data produk variant
                            $prod_data = ProductVariant::with('product')->find($variantId);

                            if (!$prod_data || !$prod_data->product) {
                                throw ValidationException::withMessages([
                                    'product_variant_id' => 'Data produk tidak ditemukan.'
                                ]);
                            }

                            // --- Stok: tambah ke produk ecommerce (gunakan model Product dari Ecommerce)
                            $dest = Product::query()
                                ->where('id', $prod_data->product->id)
                                ->lockForUpdate()
                                ->first();

                            if ($dest) {
                                $dest->increment('stock', $qty);
                            } else {
                                throw ValidationException::withMessages([
                                    'product_variant_id' => 'Produk ecommerce tidak ditemukan. Pastikan produk sudah dibuat di katalog ecommerce.'
                                ]);
                            }

                            // --- Movements (audit trail)
                            $movementOut = $trx->inventoryMovement()->create([
                                'from_warehouse_id' => $sourceId,
                                'product_variant_id' => $variantId,
                                'qty_change' => -$qty,
                                'type' => 'out',
                                'occurred_at' => $occurred,
                                'remarks' => $remarks ?? '-',
                                'created_by' => auth()->id(),
                            ]);

                            $trx->inventoryMovement()->create([
                                'from_warehouse_id' => $sourceId,
                                'product_variant_id' => $variantId,
                                'qty_change' => $qty,
                                'type' => 'in',
                                'occurred_at' => $occurred,
                                'remarks' => $remarks ?? '-',
                                'created_by' => auth()->id(),
                            ]);

                            $firstMovement = $movementOut;
                        } else {
                            // --- Header transaksi (transfer antar gudang)
                            /** @var \App\Models\Inventory\Transaction $trx */
                            $trx = Transaction::create([
                                'type' => 'pemindahan',
                                'transaction_date' => $occurred,
                                'source_warehouse_id' => $sourceId,
                                'destination_warehouse_id' => $destId,
                                'posted_at' => now(),
                                'created_by' => auth()->id(),
                                'remarks' => $remarks,
                            ]);

                            // --- Stok: kurangi sumber (lock)
                            $source = WarehouseVariantStock::query()
                                ->where('warehouse_id', $sourceId)
                                ->where('product_variant_id', $variantId)
                                ->lockForUpdate()
                                ->first();

                            if (! $source || $source->qty < $qty) {
                                throw ValidationException::withMessages([
                                    'qty_change' => 'Stok tidak mencukupi di gudang sumber.'
                                ]);
                            }
                            $source->decrement('qty', $qty);

                            // --- Stok: tambah tujuan (lock / create if missing)
                            $dest = WarehouseVariantStock::query()
                                ->where('warehouse_id', $destId)
                                ->where('product_variant_id', $variantId)
                                ->lockForUpdate()
                                ->first();

                            if ($dest) {
                                $dest->increment('qty', $qty);
                            } else {
                                WarehouseVariantStock::create([
                                    'warehouse_id' => $destId,
                                    'product_variant_id' => $variantId,
                                    'qty' => $qty,
                                    'reserved_qty' => 0,
                                ]);
                            }

                            // --- Movements (audit trail)
                            $movementOut = $trx->inventoryMovement()->create([
                                'from_warehouse_id' => $sourceId,
                                'to_warehouse_id' => $destId,
                                'product_variant_id' => $variantId,
                                'qty_change' => -$qty,
                                'type' => 'out',
                                'occurred_at' => $occurred,
                                'remarks' => $remarks ?? '-',
                                'created_by' => auth()->id(),
                            ]);

                            $trx->inventoryMovement()->create([
                                'from_warehouse_id' => $sourceId,
                                'to_warehouse_id' => $destId,
                                'product_variant_id' => $variantId,
                                'qty_change' => $qty,
                                'type' => 'in',
                                'occurred_at' => $occurred,
                                'remarks' => $remarks ?? '-',
                                'created_by' => auth()->id(),
                            ]);

                            $firstMovement = $movementOut;
                        }
                        DB::commit();

                        Notification::make()
                            ->title('Pergerakan inventory berhasil dibuat')
                            ->success()
                            ->send();

                        return $firstMovement;
                    } catch (ValidationException $ve) {
                        DB::rollBack();
                        throw $ve;
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                        throw $e;
                    }
                }),
            Action::make('Adjust Stock')
                ->visible(fn (): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-transaction'))
                ->requiresConfirmation()
                ->form([
                    Select::make('from_warehouse_id')
                        ->relationship(name: 'from_warehouse', titleAttribute: 'name')
                        ->searchable()
                        ->preload()
                        ->reactive(),
                    Select::make('product_variant_id')
                        ->label('Varian Produk (ada di gudang)')
                        ->options(function (callable $get) {
                            $wid = $get('from_warehouse_id');
                            if ($wid === null && (int) $wid <= 0) {
                                return [];
                            } else {
                                $x = WarehouseVariantStock::query()
                                    ->where('warehouse_id', $wid)
                                    ->join('product_variants as pv', 'pv.id', '=', 'warehouse_variant_stocks.product_variant_id')
                                    ->orderBy('pv.sku_variant')
                                    ->pluck('pv.sku_variant', 'warehouse_variant_stocks.product_variant_id')
                                    ->toArray();

                                return array_map(function ($variant) {
                                    return $variant;
                                }, $x);
                            }
                        })
                        ->searchable()
                        ->reactive()
                        ->required(),
                    TextInput::make('qty')
                        ->label('Adjustment Qty')
                        ->helperText('Masukkan jumlah penyesuaian untuk varian produk ini. Masukkan dalam angka positif untuk menambah stok.')
                        ->numeric()->minValue(1),
                ])
                ->action(function (array $data): void {
                    DB::beginTransaction();
                    try {
                        $warehouseId = (int) ($data['from_warehouse_id'] ?? 0);
                        $variantId = (int) ($data['product_variant_id'] ?? 0);
                        $qty = (int) ($data['qty'] ?? 0);

                        if ($warehouseId <= 0 || $variantId <= 0 || $qty <= 0) {
                            Notification::make()
                                ->title('Data tidak valid untuk penyesuaian stok')
                                ->danger()
                                ->send();
                            DB::rollBack();

                            return;
                        }

                        // Lock the stock row if exists
                        $stock = WarehouseVariantStock::query()
                            ->where('warehouse_id', $warehouseId)
                            ->where('product_variant_id', $variantId)
                            ->lockForUpdate()
                            ->first();

                        if ($stock) {
                            $stock->increment('qty', $qty);
                        } else {
                            WarehouseVariantStock::create([
                                'warehouse_id' => $warehouseId,
                                'product_variant_id' => $variantId,
                                'qty' => $qty,
                                'reserved_qty' => 0,
                            ]);
                        }

                        DB::commit();

                        Notification::make()
                            ->title('Penyesuaian stok berhasil')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Gagal menyesuaikan stok: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            ImportAction::make()
                ->visible(fn (): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('import-transaction'))
                ->importer(InventoryMovementImporter::class),
            ExportAction::make()
                ->visible(fn (): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-transaction'))
                ->exporter(InventoryMovementExporter::class),
        ];
    }
}
