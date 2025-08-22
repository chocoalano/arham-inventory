<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Transactions\Pages;

use App\AppPanel\Clusters\Inventory\Resources\Transactions\TransactionResource;
use App\Models\Inventory\Transaction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();
        if ($user && !$user->hasRole('Superadmin')) {
            $data['source_warehouse_id'] = $user->warehouse_id;
        }
        return $data;
    }

    private const STOCK_TABLE = 'warehouse_variant_stocks';
    private const MOVEMENT_TABLE = 'inventory_movements';

    /**
     * Update record transaksi.
     * Catatan: method applySales/applyReturns/applyTransfers & createInvoice
     * harus tersedia di kelas ini (atau trait yang di-use).
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Pastikan tipe model benar
        if (!$record instanceof Transaction) {
            throw new \RuntimeException('EditTransaction menerima model yang bukan Transaction.');
        }

        /** @var Transaction $transaction */
        $transaction = $record->load('details');

        $details = array_values($data['details'] ?? []);
        if (empty($details)) {
            throw ValidationException::withMessages(['details' => 'Minimal 1 item harus diisi.']);
        }

        // Validasi kebutuhan warehouse per tipe
        $type = (string) ($data['type'] ?? $transaction->type ?? '');
        $headerSource = (int) ($data['source_warehouse_id'] ?? $transaction->source_warehouse_id ?? 0);
        $headerDest = (int) ($data['destination_warehouse_id'] ?? $transaction->destination_warehouse_id ?? 0);

        if ($type === 'pemindahan') {
            if ($headerSource <= 0) {
                throw ValidationException::withMessages(['source_warehouse_id' => 'Gudang sumber wajib diisi untuk pemindahan.']);
            }
            if ($headerDest <= 0) {
                throw ValidationException::withMessages(['destination_warehouse_id' => 'Gudang tujuan wajib diisi untuk pemindahan.']);
            }
            if ($headerSource === $headerDest) {
                throw ValidationException::withMessages(['destination_warehouse_id' => 'Gudang sumber & tujuan tidak boleh sama.']);
            }
        }

        if ($type === 'pengembalian') {
            if ($headerDest <= 0) {
                throw ValidationException::withMessages(['destination_warehouse_id' => 'Gudang tujuan wajib diisi untuk pengembalian.']);
            }
            if ($headerSource <= 0) {
                $barisAdaSource = collect($details)->every(
                    fn($r) => (int) ($r['source_warehouse_id'] ?? 0) > 0
                );
                if (!$barisAdaSource) {
                    throw ValidationException::withMessages([
                        'source_warehouse_id' => 'Gudang asal wajib diisi di header atau per-baris untuk pengembalian.',
                    ]);
                }
            }
        }

        // Ringkasan awal payload
        $totalQty = 0;
        $grandTotal = 0;
        foreach ($details as $row) {
            $qty = (int) ($row['qty'] ?? 0);
            $lineTotal = (int) ($row['line_total'] ?? 0);
            if ($qty < 0) {
                throw ValidationException::withMessages(['details' => 'Qty tidak boleh negatif.']);
            }
            $totalQty += $qty;
            $grandTotal += max(0, $lineTotal);
        }

        $occurredAt = isset($data['transaction_date'])
            ? Carbon::parse($data['transaction_date'])
            : ($transaction->transaction_date ?? now());

        $reference = $data['reference_number']
            ?? ($transaction->reference_number ?: ('TRX-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(5))));
        $status = (string) ($data['status'] ?? $transaction->status ?? 'posted');

        DB::beginTransaction();
        try {
            // 1) Rollback efek transaksi lama (stok & movement)
            $this->rollbackStockAndMovements($transaction);

            // 2) Update HEADER transaksi
            $transaction->update([
                'reference_number' => $reference,
                'type' => $type,
                'transaction_date' => $occurredAt,
                'source_warehouse_id' => $headerSource ?: null,
                'destination_warehouse_id' => $headerDest ?: null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_full_address' => $data['customer_full_address'] ?? null,
                'item_count' => $totalQty,
                'grand_total' => $grandTotal,
                'status' => $status,
                'posted_at' => $data['posted_at'] ?? $transaction->posted_at ?? now(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            // 3) Replace DETAIL transaksi
            $transaction->details()->delete();

            $detailPayload = [];
            foreach ($details as $row) {
                $detailPayload[] = [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'product_variant_id' => (int) ($row['product_variant_id'] ?? 0),
                    'warehouse_id' => (int) ($headerSource ?: $headerDest),
                    'qty' => (int) ($row['qty'] ?? 0),
                    'price' => (int) ($row['price'] ?? 0),
                    'discount_amount' => (int) ($row['discount_amount'] ?? 0),
                    'line_total' => (int) ($row['line_total'] ?? 0),
                ];
            }
            $transaction->details()->createMany($detailPayload);

            // 4) Terapkan mutasi stok + movement sesuai tipe baru
            switch ($type) {
                case 'penjualan':
                    $this->applySales($transaction, $details, $headerSource, $occurredAt);
                    $this->createInvoice($transaction);
                    break;

                case 'pengembalian':
                    $this->applyReturns($transaction, $details, $headerSource, $headerDest, $occurredAt);
                    break;

                case 'pemindahan':
                    $this->applyTransfers($transaction, $details, $headerSource, $headerDest, $occurredAt);
                    break;

                default:
                    // Tambah handler lain jika diperlukan
                    break;
            }

            DB::commit();

            Notification::make()
                ->title('Transaksi Berhasil Diperbarui')
                ->success()
                ->send();

            return $transaction->fresh('details');
        } catch (ValidationException $ve) {
            DB::rollBack();
            throw $ve;
        } catch (\Throwable $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal Memperbarui Transaksi')
                ->body('Terjadi kesalahan saat memperbarui inventaris: ' . $e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    /**
     * Rollback efek transaksi lama (revert stok & hapus movement).
     * Pastikan method ini sama persis dengan versi di CreatePage agar konsisten.
     */
    private function rollbackStockAndMovements(Transaction $trx): void
    {
        $movements = DB::table(self::MOVEMENT_TABLE)
            ->where('transaction_id', $trx->id)
            ->orderBy('id') // deterministik
            ->get();

        foreach ($movements as $mv) {
            $qty = (int) $mv->qty_change;

            if ($mv->type === 'out') {
                // Kembalikan stok ke gudang sumber
                $warehouseId = (int) $mv->from_warehouse_id;
                $variantId = (int) $mv->product_variant_id;

                $row = DB::table(self::STOCK_TABLE)
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                if ($row) {
                    DB::table(self::STOCK_TABLE)
                        ->where('id', $row->id)
                        ->update(['qty' => (int) $row->qty + $qty, 'updated_at' => now()]);
                } else {
                    DB::table(self::STOCK_TABLE)->insert([
                        'warehouse_id' => $warehouseId,
                        'product_variant_id' => $variantId,
                        'qty' => $qty,
                        'reserved_qty' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($mv->type === 'in') {
                // Kurangi stok dari gudang tujuan (harus cukup)
                $warehouseId = (int) $mv->to_warehouse_id;
                $variantId = (int) $mv->product_variant_id;

                $row = DB::table(self::STOCK_TABLE)
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                $current = (int) ($row->qty ?? 0);
                if ($current < $qty) {
                    throw ValidationException::withMessages([
                        'stock' => "Rollback gagal: stok tujuan (wh {$warehouseId}, var {$variantId}) tidak mencukupi. Tersedia {$current}, perlu dikurangi {$qty}.",
                    ]);
                }

                DB::table(self::STOCK_TABLE)
                    ->where('id', $row->id)
                    ->update(['qty' => $current - $qty, 'updated_at' => now()]);
            }
        }

        // Hapus movement lama
        DB::table(self::MOVEMENT_TABLE)
            ->where('transaction_id', $trx->id)
            ->delete();
    }
}
