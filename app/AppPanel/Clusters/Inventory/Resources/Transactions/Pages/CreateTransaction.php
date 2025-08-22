<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Transactions\Pages;

use App\AppPanel\Clusters\Inventory\Resources\Transactions\TransactionResource;
use App\Models\Inventory\Invoice;
use App\Models\Inventory\Transaction;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        if ($user && !$user->hasRole('Superadmin')) {
            $data['source_warehouse_id'] = $user->warehouse_id;
        }
        return $data;
    }
    private const STOCK_TABLE = 'warehouse_variant_stocks';
    private const MOVEMENT_TABLE = 'inventory_movements';

    protected function handleRecordCreation(array $data): Model
    {
        $details = array_values($data['details'] ?? []);
        if (empty($details)) {
            throw ValidationException::withMessages(['details' => 'Minimal 1 item harus diisi.']);
        }

        // Validasi kebutuhan warehouse per tipe
        $type = (string) ($data['type'] ?? '');
        $headerSource = (int) ($data['source_warehouse_id'] ?? 0);
        $headerDest = (int) ($data['destination_warehouse_id'] ?? 0);

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
            // source bisa berasal dari baris (misal return dari toko tertentu), fallback ke header bila ada
            if ($headerSource <= 0) {
                // tidak wajib di header jika setiap baris membawa source_warehouse_id
                $barisAdaSource = collect($details)->every(fn($r) => (int) ($r['source_warehouse_id'] ?? 0) > 0);
                if (!$barisAdaSource) {
                    throw ValidationException::withMessages(['source_warehouse_id' => 'Gudang asal wajib diisi di header atau per-baris untuk pengembalian.']);
                }
            }
        }

        // Hitung ringkasan awal dari payload
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
            : now();

        $reference = $data['reference_number'] ?? ('TRX-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(5)));
        $status = (string) ($data['status'] ?? 'posted');

        DB::beginTransaction();
        try {
            // 1) Simpan HEADER transaksi
            /** @var Transaction $transaction */
            $transaction = Transaction::create([
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
                'posted_at' => $data['posted_at'] ?? now(),
                'created_by' => Auth::id(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            // 2) Simpan DETAIL transaksi (normalisasi field warehouse per baris)
            $detailPayload = [];
            foreach ($details as $row) {
                $detailPayload[] = [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'product_variant_id' => (int) ($row['product_variant_id'] ?? 0),
                    'warehouse_id' => (int) ($headerSource ?? $headerDest),
                    'qty' => (int) ($row['qty'] ?? 0),
                    'price' => (int) ($row['price'] ?? 0),
                    'discount_amount' => (int) ($row['discount_amount'] ?? 0),
                    'line_total' => (int) ($row['line_total'] ?? 0),
                ];
            }
            $transaction->details()->createMany($detailPayload);

            // 3) Mutasi stok + Movement
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
                    // Jika ada tipe lain, tambahkan handler di sini.
                    break;
            }

            DB::commit();

            Notification::make()
                ->title('Transaksi Berhasil Dibuat')
                ->success()
                ->send();

            return $transaction->fresh('details');
        } catch (ValidationException $ve) {
            DB::rollBack();
            throw $ve;
        } catch (\Throwable $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal Membuat Transaksi')
                ->body('Terjadi kesalahan saat memproses inventaris: ' . $e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    /* ======================== Helper Mutasi Stok ======================== */

    /**
     * Penjualan: stok keluar dari gudang sumber.
     * Movement: satu baris OUT (from=source, to=NULL).
     */
    private function applySales(Transaction $trx, array $details, int $defaultSourceWarehouseId, Carbon $occurredAt): void
    {
        foreach ($details as $row) {
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            if ($variantId <= 0 || $qty <= 0)
                continue;

            $wSource = (int) ($row['source_warehouse_id'] ?? $defaultSourceWarehouseId);
            if ($wSource <= 0) {
                throw ValidationException::withMessages(['source_warehouse_id' => "Gudang sumber tidak valid untuk varian #{$variantId}."]);
            }

            // Lock stok sumber
            $srcRow = DB::table(self::STOCK_TABLE)
                ->where('warehouse_id', $wSource)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            $current = (int) ($srcRow->qty ?? 0);
            if ($current < $qty) {
                throw ValidationException::withMessages([
                    'stock' => "Stok tidak mencukupi untuk varian #{$variantId} di gudang #{$wSource}. Tersedia: {$current}, butuh: {$qty}.",
                ]);
            }

            // Update stok sumber (kurangi)
            DB::table(self::STOCK_TABLE)
                ->where('id', $srcRow->id)
                ->update(['qty' => $current - $qty, 'updated_at' => now()]);

            // Movement OUT (from=source, to=NULL)
            DB::table(self::MOVEMENT_TABLE)->insert([
                'transaction_id' => $trx->id,
                'type' => 'out',
                'from_warehouse_id' => $wSource,
                'to_warehouse_id' => null,
                'product_variant_id' => $variantId,
                'qty_change' => $qty,
                'occurred_at' => $occurredAt,
                'remarks' => $row['remarks'] ?? 'Penjualan produk',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Pengembalian: stok pindah dari gudang asal ke gudang tujuan.
     * Movement: OUT (from=source, to=dest) + IN (from=source, to=dest).
     */
    private function applyReturns(
        Transaction $trx,
        array $details,
        int $defaultSourceWarehouseId,
        int $defaultDestinationWarehouseId,
        Carbon $occurredAt
    ): void {
        foreach ($details as $row) {
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            if ($variantId <= 0 || $qty <= 0)
                continue;

            $wSource = (int) ($row['source_warehouse_id'] ?? $defaultSourceWarehouseId);
            $wDest = (int) ($row['target_warehouse_id'] ?? $defaultDestinationWarehouseId);

            if ($wDest <= 0) {
                throw ValidationException::withMessages(['destination_warehouse_id' => 'Gudang tujuan tidak valid untuk pengembalian.']);
            }
            if ($wSource <= 0) {
                throw ValidationException::withMessages(['source_warehouse_id' => 'Gudang asal tidak valid untuk pengembalian.']);
            }

            if ($wSource === $wDest) {
                // Netral: tidak ada perpindahan stok. Catat movement IN saja opsional.
                DB::table(self::MOVEMENT_TABLE)->insert([
                    'transaction_id' => $trx->id,
                    'type' => 'in',
                    'from_warehouse_id' => $wSource,
                    'to_warehouse_id' => $wDest,
                    'product_variant_id' => $variantId,
                    'qty_change' => $qty,
                    'occurred_at' => $occurredAt,
                    'remarks' => $row['remarks'] ?? 'Return (same warehouse)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                continue;
            }

            // Lock deterministik untuk hindari deadlock
            $pairs = [
                ['warehouse_id' => $wSource, 'product_variant_id' => $variantId],
                ['warehouse_id' => $wDest, 'product_variant_id' => $variantId],
            ];
            usort(
                $pairs,
                fn($a, $b) =>
                [$a['warehouse_id'], $a['product_variant_id']] <=> [$b['warehouse_id'], $b['product_variant_id']]
            );

            $locked = [];
            foreach ($pairs as $p) {
                $key = $p['warehouse_id'] . ':' . $p['product_variant_id'];
                $locked[$key] = DB::table(self::STOCK_TABLE)
                    ->where('warehouse_id', $p['warehouse_id'])
                    ->where('product_variant_id', $p['product_variant_id'])
                    ->lockForUpdate()
                    ->first();
            }

            $srcKey = $wSource . ':' . $variantId;
            $destKey = $wDest . ':' . $variantId;

            $srcRow = $locked[$srcKey] ?? null;
            $destRow = $locked[$destKey] ?? null;

            $srcQtyBefore = (int) ($srcRow->qty ?? 0);
            if ($srcQtyBefore < $qty) {
                throw ValidationException::withMessages([
                    'stock' => "Stok tidak mencukupi di gudang asal (ID: {$wSource}) untuk variant {$variantId}. Tersedia {$srcQtyBefore}, butuh {$qty}.",
                ]);
            }

            // Kurangi sumber
            DB::table(self::STOCK_TABLE)
                ->where('id', $srcRow->id)
                ->update(['qty' => $srcQtyBefore - $qty, 'updated_at' => now()]);

            // Tambah tujuan
            if ($destRow) {
                DB::table(self::STOCK_TABLE)
                    ->where('id', $destRow->id)
                    ->update(['qty' => (int) $destRow->qty + $qty, 'updated_at' => now()]);
            } else {
                DB::table(self::STOCK_TABLE)->insert([
                    'warehouse_id' => $wDest,
                    'product_variant_id' => $variantId,
                    'qty' => $qty,
                    'reserved_qty' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Movement OUT (source -> dest)
            DB::table(self::MOVEMENT_TABLE)->insert([
                'transaction_id' => $trx->id,
                'type' => 'out',
                'from_warehouse_id' => $wSource,
                'to_warehouse_id' => $wDest,
                'product_variant_id' => $variantId,
                'qty_change' => $qty,
                'occurred_at' => $occurredAt,
                'remarks' => $row['remarks'] ?? 'Return keluar dari gudang asal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Movement IN (source -> dest)
            DB::table(self::MOVEMENT_TABLE)->insert([
                'transaction_id' => $trx->id,
                'type' => 'in',
                'from_warehouse_id' => $wSource,
                'to_warehouse_id' => $wDest,
                'product_variant_id' => $variantId,
                'qty_change' => $qty,
                'occurred_at' => $occurredAt,
                'remarks' => $row['remarks'] ?? 'Return masuk ke gudang tujuan',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Pemindahan: stok keluar dari sumber lalu masuk ke tujuan.
     * Movement: OUT (from=source, to=dest) + IN (from=source, to=dest).
     */
    private function applyTransfers(
        Transaction $trx,
        array $details,
        int $defaultSourceWarehouseId,
        int $defaultDestinationWarehouseId,
        Carbon $occurredAt
    ): void {
        if ($defaultSourceWarehouseId === $defaultDestinationWarehouseId) {
            throw ValidationException::withMessages([
                'destination_warehouse_id' => 'Gudang sumber & tujuan tidak boleh sama.',
            ]);
        }

        foreach ($details as $row) {
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            if ($variantId <= 0 || $qty <= 0)
                continue;

            // Override per baris (mendukung berbagai nama field)
            $wSource = (int) ($row['source_warehouse_id']
                ?? $row['warehouse_id']        // compat lama
                ?? $defaultSourceWarehouseId);

            $wDest = (int) ($row['target_warehouse_id']
                ?? $row['dest_warehouse_id']
                ?? $row['warehouse_id_dest']   // jika ada
                ?? $defaultDestinationWarehouseId);

            if ($wSource <= 0 || $wDest <= 0) {
                throw ValidationException::withMessages([
                    'details' => 'Gudang sumber/tujuan tidak valid pada salah satu baris.',
                ]);
            }
            if ($wSource === $wDest) {
                throw ValidationException::withMessages([
                    'details' => 'Gudang sumber dan tujuan pada baris tidak boleh sama.',
                ]);
            }

            // Lock deterministik kedua baris stok
            $pairs = [
                ['warehouse_id' => $wSource, 'product_variant_id' => $variantId],
                ['warehouse_id' => $wDest, 'product_variant_id' => $variantId],
            ];
            usort(
                $pairs,
                fn($a, $b) =>
                [$a['warehouse_id'], $a['product_variant_id']] <=> [$b['warehouse_id'], $b['product_variant_id']]
            );

            $locked = [];
            foreach ($pairs as $p) {
                $key = $p['warehouse_id'] . ':' . $p['product_variant_id'];
                $locked[$key] = DB::table(self::STOCK_TABLE)
                    ->where('warehouse_id', $p['warehouse_id'])
                    ->where('product_variant_id', $p['product_variant_id'])
                    ->lockForUpdate()
                    ->first();
            }

            $srcKey = $wSource . ':' . $variantId;
            $destKey = $wDest . ':' . $variantId;

            $srcRow = $locked[$srcKey] ?? null;
            $destRow = $locked[$destKey] ?? null;

            $current = (int) ($srcRow->qty ?? 0);
            if ($current < $qty) {
                throw ValidationException::withMessages([
                    'stock' => "Stok tidak mencukupi untuk varian #{$variantId} di gudang #{$wSource}. Tersedia: {$current}, butuh: {$qty}.",
                ]);
            }

            // Kurangi sumber
            DB::table(self::STOCK_TABLE)
                ->where('id', $srcRow->id)
                ->update(['qty' => $current - $qty, 'updated_at' => now()]);

            // Tambah tujuan
            if ($destRow) {
                DB::table(self::STOCK_TABLE)
                    ->where('id', $destRow->id)
                    ->update(['qty' => (int) $destRow->qty + $qty, 'updated_at' => now()]);
            } else {
                DB::table(self::STOCK_TABLE)->insert([
                    'warehouse_id' => $wDest,
                    'product_variant_id' => $variantId,
                    'qty' => $qty,
                    'reserved_qty' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Movement OUT (source -> dest)
            DB::table(self::MOVEMENT_TABLE)->insert([
                'transaction_id' => $trx->id,
                'type' => 'out',
                'from_warehouse_id' => $wSource,
                'to_warehouse_id' => $wDest,
                'product_variant_id' => $variantId,
                'qty_change' => $qty,
                'occurred_at' => $occurredAt,
                'remarks' => $row['remarks'] ?? 'Pemindahan keluar',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Movement IN (source -> dest)
            DB::table(self::MOVEMENT_TABLE)->insert([
                'transaction_id' => $trx->id,
                'type' => 'in',
                'from_warehouse_id' => $wSource,
                'to_warehouse_id' => $wDest,
                'product_variant_id' => $variantId,
                'qty_change' => $qty,
                'occurred_at' => $occurredAt,
                'remarks' => $row['remarks'] ?? 'Pemindahan masuk',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /* ======================== Invoice ======================== */

    private function createInvoice(Transaction $transaction): void
    {
        if ($transaction->type !== 'penjualan') {
            return;
        }

        Invoice::create([
            'transaction_id' => $transaction->id,
            'invoice_number' => 'INV-' . Str::upper(Str::random(8)),
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'subtotal' => $transaction->grand_total,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_fee' => 0,
            'total_amount' => $transaction->grand_total,
            'paid_amount' => 0,
            'is_paid' => false,
        ]);
    }
}
