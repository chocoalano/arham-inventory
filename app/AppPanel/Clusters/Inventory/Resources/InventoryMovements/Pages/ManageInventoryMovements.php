<?php

namespace App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Pages;

use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\InventoryMovementResource;
use App\Filament\Exports\InventoryMovementExporter;
use App\Filament\Imports\InventoryMovementImporter;
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
                            Notification::make()
                                ->title('Qty harus lebih dari 0.')
                                ->danger()
                                ->send();
                        }
                        if ($sourceId <= 0 || $destId <= 0 || $sourceId === $destId) {
                            Notification::make()
                                ->title('Gudang sumber & tujuan wajib diisi dan tidak boleh sama.')
                                ->danger()
                                ->send();
                        }
                        if (!ProductVariant::query()->whereKey($variantId)->exists()) {
                            Notification::make()
                                ->title('Varian tidak valid.')
                                ->danger()
                                ->send();
                        }

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

                        if (!$source || $source->qty < $qty) {
                            Notification::make()
                                ->title('Stok tidak mencukupi di gudang sumber.')
                                ->danger()
                                ->send();
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
                        // Pastikan di model Transaction ada:
                        // public function movements() { return $this->hasMany(\App\Models\Inventory\InventoryMovement::class); }
                        $trx->inventoryMovement()->create([
                            'from_warehouse_id' => $sourceId,
                            'to_warehouse_id' => $destId,
                            'product_variant_id' => $variantId,
                            'qty_change' => -$qty,
                            'type' => 'out', // atau 'out'
                            'occurred_at' => $occurred,
                            'remarks' => $remarks ?? '-',
                            'created_by' => auth()->id(),
                        ]);

                        $trx->inventoryMovement()->create([
                            'from_warehouse_id' => $sourceId,
                            'to_warehouse_id' => $destId,
                            'product_variant_id' => $variantId,
                            'qty_change' => $qty,
                            'type' => 'in', // atau 'in'
                            'occurred_at' => $occurred,
                            'remarks' => $remarks ?? '-',
                            'created_by' => auth()->id(),
                        ]);

                        DB::commit();
                        return $trx;
                    } catch (ValidationException $ve) {
                        dd($ve);
                        DB::rollBack();
                        throw $ve;
                    }
                }),
            ImportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('import-transaction'))
                ->importer(InventoryMovementImporter::class),
            ExportAction::make()
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-transaction'))
                ->exporter(InventoryMovementExporter::class),
            Action::make('Adjust Stock')
                ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin') || auth()->user()?->hasPermissionTo('export-transaction'))
                ->requiresConfirmation()
                ->form([
                    Select::make('from_warehouse_id')
                        ->relationship(name: 'from_warehouse', titleAttribute: 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('product_variant_id')
                        ->relationship(name: 'variant', titleAttribute: 'sku_variant')
                        ->searchable()
                        ->preload(),
                    TextInput::make('qty')
                        ->label('Adjustment Qty')
                        ->helperText('Masukkan jumlah penyesuaian untuk varian produk ini. Masukkan dalam angka.')
                        ->numeric()->minValue(1),
                ])
                ->action(function (array $data): void {
                    dd($data);
                })
        ];
    }
}
