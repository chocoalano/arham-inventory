<?php

namespace App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Pages;

use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\InventoryMovementResource;
use App\Filament\Exports\InventoryMovementExporter;
use App\Filament\Imports\InventoryMovementImporter;
use App\Models\Ecommerce\Product;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseVariantStock;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ManageInventoryMovements extends ManageRecords
{
    protected static string $resource = InventoryMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // =========================================================
            // CREATE INVENTORY MOVEMENT (transfer / to ecommerce)
            // + VALIDASI STOK TEGAS: tidak boleh minus, cek (qty - reserved_qty)
            // =========================================================
            CreateAction::make()
                ->using(function (array $data): Model {
                    // Helper: kirim Notification danger lalu throw Halt
                    $failWith = function (string $message): never {
                        Notification::make()
                            ->title('Validasi Gagal')
                            ->body($message)
                            ->danger()
                            ->persistent()
                            ->send();
                        throw new Halt();
                    };

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
                            $failWith('Qty harus lebih dari 0.');
                        }

                        if ($sourceId <= 0) {
                            $failWith('Gudang sumber wajib diisi.');
                        }

                        if ($destId < 0 || $sourceId === $destId) {
                            $failWith('Gudang tujuan tidak valid atau sama dengan gudang sumber.');
                        }

                        if (!ProductVariant::query()->whereKey($variantId)->exists()) {
                            $failWith('Varian produk tidak valid.');
                        }

                        // =========================================================
                        // DEST = 0 => pindah dari gudang ke "ecommerce stock"
                        // =========================================================
                        if ($destId === 0 && $sourceId !== 0) {
                            $trx = Transaction::create([
                                'type' => 'pemindahan',
                                'transaction_date' => $occurred,
                                'source_warehouse_id' => $sourceId,
                                'posted_at' => now(),
                                'created_by' => auth()->id(),
                                'remarks' => $remarks,
                            ]);

                            // --- Lock sumber
                            $source = WarehouseVariantStock::query()
                                ->where('warehouse_id', $sourceId)
                                ->where('product_variant_id', $variantId)
                                ->lockForUpdate()
                                ->first();

                            if (!$source) {
                                $failWith('Stok varian ini tidak ditemukan di gudang sumber.');
                            }

                            // --- VALIDASI TEGAS (stok tersedia = qty - reserved_qty)
                            $reserved = (int) ($source->reserved_qty ?? 0);
                            $available = (int) $source->qty - $reserved;

                            if ($available < $qty) {
                                $failWith("Stok tidak mencukupi di gudang sumber. Tersedia: {$available} (stok: {$source->qty}, reserved: {$reserved}).");
                            }

                            // --- Decrement aman (guarded, anti race condition)
                            $affected = WarehouseVariantStock::query()
                                ->whereKey($source->id)
                                ->whereRaw('(qty - COALESCE(reserved_qty,0)) >= ?', [$qty])
                                ->decrement('qty', $qty);

                            if ($affected === 0) {
                                $failWith('Stok berubah / tidak mencukupi saat diproses. Silakan coba lagi.');
                            }

                            // Ambil data produk variant
                            $prod_data = ProductVariant::with('product')->find($variantId);
                            if (!$prod_data || !$prod_data->product) {
                                $failWith('Data produk tidak ditemukan.');
                            }

                            // --- Tambah ke stok ecommerce product (lock)
                            $dest = Product::query()
                                ->where('id', $prod_data->product->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$dest) {
                                $failWith('Produk ecommerce tidak ditemukan. Pastikan produk sudah dibuat di katalog ecommerce.');
                            }

                            $dest->increment('stock', $qty);

                            // --- Movements (audit trail)
                            $movementOut = $trx->inventoryMovements()->create([
                                'from_warehouse_id' => $sourceId,
                                'product_variant_id' => $variantId,
                                'qty_change' => -$qty,
                                'type' => 'out',
                                'occurred_at' => $occurred,
                                'remarks' => $remarks ?? '-',
                                'created_by' => auth()->id(),
                            ]);

                            $trx->inventoryMovements()->create([
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
                            // =========================================================
                            // Transfer antar gudang
                            // =========================================================
                            $trx = Transaction::create([
                                'type' => 'pemindahan',
                                'transaction_date' => $occurred,
                                'source_warehouse_id' => $sourceId,
                                'destination_warehouse_id' => $destId,
                                'posted_at' => now(),
                                'created_by' => auth()->id(),
                                'remarks' => $remarks,
                            ]);

                            // --- Lock sumber
                            $source = WarehouseVariantStock::query()
                                ->where('warehouse_id', $sourceId)
                                ->where('product_variant_id', $variantId)
                                ->lockForUpdate()
                                ->first();

                            if (!$source) {
                                $failWith('Stok varian ini tidak ditemukan di gudang sumber.');
                            }

                            // --- VALIDASI TEGAS (stok tersedia = qty - reserved_qty)
                            $reserved = (int) ($source->reserved_qty ?? 0);
                            $available = (int) $source->qty - $reserved;

                            if ($available < $qty) {
                                $failWith("Stok tidak mencukupi di gudang sumber. Tersedia: {$available} (stok: {$source->qty}, reserved: {$reserved}).");
                            }

                            // --- Decrement aman (guarded)
                            $affected = WarehouseVariantStock::query()
                                ->whereKey($source->id)
                                ->whereRaw('(qty - COALESCE(reserved_qty,0)) >= ?', [$qty])
                                ->decrement('qty', $qty);

                            if ($affected === 0) {
                                $failWith('Stok berubah / tidak mencukupi saat diproses. Silakan coba lagi.');
                            }

                            // --- Lock tujuan (create if missing)
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
                            $movementOut = $trx->inventoryMovements()->create([
                                'from_warehouse_id' => $sourceId,
                                'to_warehouse_id' => $destId,
                                'product_variant_id' => $variantId,
                                'qty_change' => -$qty,
                                'type' => 'out',
                                'occurred_at' => $occurred,
                                'remarks' => $remarks ?? '-',
                                'created_by' => auth()->id(),
                            ]);

                            $trx->inventoryMovements()->create([
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
                    } catch (Halt $e) {
                        DB::rollBack();
                        throw $e;
                    } catch (\Throwable $e) {
                        DB::rollBack();

                        Notification::make()
                            ->title('Terjadi kesalahan')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        throw new Halt();
                    }
                }),

            // =========================================================
            // ADJUST STOCK
            // - Support tambah & kurangi stok
            // - Validasi tegas: pengurangan tidak boleh melebihi stok tersedia
            // - Buat record InventoryMovement untuk audit trail
            // - Matikan successNotification bawaan Filament
            // =========================================================
            Action::make('Adjust Stock')
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-transaction'))
                ->requiresConfirmation()
                ->successNotification(null)
                ->failureNotification(null)
                ->form([
                    Select::make('adjustment_type')
                        ->label('Jenis Penyesuaian')
                        ->options([
                            'add' => 'Tambah Stok',
                            'reduce' => 'Kurangi Stok',
                        ])
                        ->default('add')
                        ->required()
                        ->live(),

                    Select::make('from_warehouse_id')
                        ->label('Gudang')
                        ->options(function () {
                            return Warehouse::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (callable $set): void {
                            $set('product_variant_id', null);
                            $set('qty', null);
                        })
                        ->required(),

                    Select::make('product_variant_id')
                        ->label('Varian Produk')
                        ->options(function (callable $get) {
                            $wid = $get('from_warehouse_id');

                            if ($wid === null || (int) $wid <= 0) {
                                return [];
                            }

                            $adjustmentType = $get('adjustment_type');

                            if ($adjustmentType === 'reduce') {
                                // Kurangi: hanya tampilkan varian yang punya stok > 0
                                return WarehouseVariantStock::query()
                                    ->where('warehouse_id', (int) $wid)
                                    ->whereRaw('(COALESCE(qty,0) - COALESCE(reserved_qty,0)) > 0')
                                    ->join('product_variants as pv', 'pv.id', '=', 'warehouse_variant_stocks.product_variant_id')
                                    ->orderBy('pv.sku_variant')
                                    ->pluck('pv.sku_variant', 'warehouse_variant_stocks.product_variant_id')
                                    ->toArray();
                            }

                            // Tambah: tampilkan semua varian yang ada di gudang ini
                            return WarehouseVariantStock::query()
                                ->where('warehouse_id', (int) $wid)
                                ->join('product_variants as pv', 'pv.id', '=', 'warehouse_variant_stocks.product_variant_id')
                                ->orderBy('pv.sku_variant')
                                ->pluck('pv.sku_variant', 'warehouse_variant_stocks.product_variant_id')
                                ->toArray();
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn(callable $set) => $set('qty', null))
                        ->required(),

                    Placeholder::make('current_stock_info')
                        ->label('Info Stok Saat Ini')
                        ->content(function (callable $get) {
                            $wid = (int) ($get('from_warehouse_id') ?? 0);
                            $vid = (int) ($get('product_variant_id') ?? 0);

                            if ($wid <= 0) {
                                return 'Pilih gudang terlebih dahulu untuk melihat kondisi stok.';
                            }

                            if ($vid <= 0) {
                                return 'Pilih varian produk untuk menampilkan stok fisik, reservasi, dan stok tersedia.';
                            }

                            $adjustmentType = $get('adjustment_type') === 'reduce' ? 'reduce' : 'add';
                            $stock = WarehouseVariantStock::query()
                                ->where('warehouse_id', $wid)
                                ->where('product_variant_id', $vid)
                                ->first();

                            $qty = (int) ($stock->qty ?? 0);
                            $reserved = (int) ($stock->reserved_qty ?? 0);
                            $available = max(0, $qty - $reserved);
                            $baseInfo = "Stok fisik: {$qty} | Reserved: {$reserved} | Tersedia: {$available}.";

                            if ($adjustmentType === 'reduce') {
                                if ($available <= 0) {
                                    return "{$baseInfo} Pengurangan tidak dapat dilakukan karena stok tersedia 0.";
                                }

                                return "{$baseInfo} Maksimum pengurangan saat ini: {$available} unit.";
                            }

                            return "{$baseInfo} Penambahan akan menambah stok fisik di gudang ini.";
                        })
                        ->columnSpanFull()
                        ->reactive(),

                    TextInput::make('qty')
                        ->label('Jumlah Penyesuaian')
                        ->helperText('Masukkan jumlah unit yang akan ditambah/dikurangi.')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->maxValue(function (callable $get) {
                            // Batasi max hanya untuk pengurangan stok
                            if ($get('adjustment_type') !== 'reduce') {
                                return null;
                            }
                            $wid = (int) ($get('from_warehouse_id') ?? 0);
                            $vid = (int) ($get('product_variant_id') ?? 0);
                            if ($wid <= 0 || $vid <= 0) {
                                return 0;
                            }
                            return (int) (WarehouseVariantStock::query()
                                ->where('warehouse_id', $wid)
                                ->where('product_variant_id', $vid)
                                ->selectRaw('COALESCE(qty,0) - COALESCE(reserved_qty,0) AS soh')
                                ->value('soh') ?? 0);
                        })
                        ->reactive(),

                    TextInput::make('remarks')
                        ->label('Catatan')
                        ->helperText('Opsional: alasan penyesuaian stok.'),
                ])
                ->action(function (array $data, Action $action): void {
                    // Helper: kirim Notification danger lalu halt
                    $failWith = function (string $message) use ($action): never {
                        Notification::make()
                            ->title('Validasi Gagal')
                            ->body($message)
                            ->danger()
                            ->persistent()
                            ->send();
                        $action->halt();
                    };

                    DB::beginTransaction();

                    try {
                        $adjustmentType = $data['adjustment_type'] ?? 'add';
                        $warehouseId = (int) ($data['from_warehouse_id'] ?? 0);
                        $variantId = (int) ($data['product_variant_id'] ?? 0);
                        $qty = (int) abs($data['qty'] ?? 0);
                        $remarks = $data['remarks'] ?? null;

                        if ($warehouseId <= 0) {
                            $failWith('Gudang wajib diisi.');
                        }

                        if ($variantId <= 0) {
                            $failWith('Varian wajib diisi.');
                        }

                        if ($qty <= 0) {
                            $failWith('Qty harus lebih dari 0.');
                        }

                        // Lock the stock row
                        $stock = WarehouseVariantStock::query()
                            ->where('warehouse_id', $warehouseId)
                            ->where('product_variant_id', $variantId)
                            ->lockForUpdate()
                            ->first();

                        if ($adjustmentType === 'reduce') {
                            // --- KURANGI STOK ---
                            if (!$stock) {
                                $failWith('Stok varian ini tidak ditemukan di gudang.');
                            }

                            $reserved = (int) ($stock->reserved_qty ?? 0);
                            $available = (int) $stock->qty - $reserved;

                            if ($available < $qty) {
                                $failWith("Stok tidak mencukupi. Tersedia: {$available} (stok: {$stock->qty}, reserved: {$reserved}).");
                            }

                            // Guarded decrement (anti race condition)
                            $affected = WarehouseVariantStock::query()
                                ->whereKey($stock->id)
                                ->whereRaw('(qty - COALESCE(reserved_qty,0)) >= ?', [$qty])
                                ->decrement('qty', $qty);

                            if ($affected === 0) {
                                $failWith('Stok berubah saat diproses. Silakan coba lagi.');
                            }

                            $qtyChange = -$qty;
                            $movementType = 'out';
                        } else {
                            // --- TAMBAH STOK ---
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

                            $qtyChange = $qty;
                            $movementType = 'in';
                        }

                        // --- Buat record InventoryMovement untuk audit trail ---
                        $adjustLabel = $adjustmentType === 'add' ? 'Penambahan' : 'Pengurangan';
                        $movementRemarks = "Penyesuaian stok ({$adjustLabel}): " . ($adjustmentType === 'add' ? '+' : '-') . $qty
                            . ($remarks ? " | {$remarks}" : '');

                        InventoryMovement::create([
                            'transaction_id' => null,
                            'from_warehouse_id' => $warehouseId,
                            'to_warehouse_id' => null,
                            'product_variant_id' => $variantId,
                            'qty_change' => $qtyChange,
                            'type' => $movementType,
                            'occurred_at' => now(),
                            'remarks' => $movementRemarks,
                            'created_by' => auth()->id(),
                        ]);

                        DB::commit();

                        Notification::make()
                            ->title('Penyesuaian stok berhasil')
                            ->body("Stok varian berhasil di" . ($adjustmentType === 'add' ? 'tambah' : 'kurangi') . " sebanyak {$qty} unit.")
                            ->success()
                            ->send();
                    } catch (Halt $e) {
                        DB::rollBack();
                        throw $e;
                    } catch (\Throwable $e) {
                        DB::rollBack();

                        Notification::make()
                            ->title('Gagal menyesuaikan stok')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),

            ImportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('import-transaction'))
                ->importer(InventoryMovementImporter::class),

            ExportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-transaction'))
                ->exporter(InventoryMovementExporter::class),
        ];
    }
}
