<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\Invoice;
use App\Models\Inventory\Payment;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\TransactionDetail;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseVariantStock;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(['penjualan', 'pemindahan', 'pengembalian']);
        $dt = $this->faker->dateTimeBetween('-60 days', 'now');

        $src = $this->faker->boolean(85) ? Warehouse::factory() : null;
        $dst = $type === 'pemindahan'
            ? Warehouse::factory()
            : ($this->faker->boolean(40) ? Warehouse::factory() : null);

        return [
            'reference_number' => strtoupper('TX-' . Carbon::instance($dt)->format('Ymd') . '-' . Str::upper(Str::random(5))),
            'type' => $type,
            'transaction_date' => Carbon::instance($dt)->toDateTimeString(),
            'source_warehouse_id' => $src,
            'destination_warehouse_id' => $dst,
            'customer_name' => $type === 'penjualan' ? $this->faker->name() : null,
            'customer_phone' => $type === 'penjualan' ? '08' . $this->faker->numerify('##########') : null,
            'customer_full_address' => $type === 'penjualan' ? $this->faker->address() : null,
            'item_count' => 0,
            'grand_total' => 0,
            'status' => $this->faker->randomElement(['draft', 'posted']),
            'posted_at' => null,
            'created_by' => null, // set jika punya users
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }

    public function draft(): self
    {
        return $this->state(['status' => 'draft', 'posted_at' => null]);
    }
    public function posted(): self
    {
        return $this->state(function (array $attrs) {
            $ts = Carbon::parse($attrs['transaction_date'] ?? now())->addHours(2);
            return ['status' => 'posted', 'posted_at' => $ts->toDateTimeString()];
        });
    }

    public function sale(): self
    {
        return $this->state(['type' => 'penjualan']);
    }
    public function transfer(): self
    {
        return $this->state(['type' => 'pemindahan']);
    }
    public function returnIn(): self
    {
        return $this->state(['type' => 'pengembalian']);
    }

    /**
     * Generate detail, total, movements (+ invoice/payment utk penjualan) sesudah create.
     */
    public function withAutoDetails(int $min = 2, int $max = 4): self
    {
        return $this->afterCreating(function (Transaction $tx) use ($min, $max) {
            // Siapkan gudang wajib untuk transfer
            if ($tx->type === 'pemindahan') {
                if (!$tx->source_warehouse_id)
                    $tx->source_warehouse_id = Warehouse::query()->inRandomOrder()->value('id') ?? Warehouse::factory()->create()->id;
                if (!$tx->destination_warehouse_id || $tx->destination_warehouse_id === $tx->source_warehouse_id) {
                    $tx->destination_warehouse_id = Warehouse::query()->where('id', '!=', $tx->source_warehouse_id)->inRandomOrder()->value('id')
                        ?? Warehouse::factory()->create()->id;
                }
                $tx->save();
            }

            $variantPool = ProductVariant::query()
                ->inRandomOrder()
                ->limit(300)
                ->get(['id', 'product_id', 'price']);  // <- get() mengembalikan Collection of Models

            if ($variantPool->isEmpty()) {
                return;
            }

            $need = min($variantPool->count(), max(1, random_int($min, $max)));
            $picked = $variantPool->random($need);

            // Hasil random(1) bisa Model tunggal → bungkus jadi Collection
            if (!($picked instanceof \Illuminate\Support\Collection)) {
                $picked = collect([$picked]);
            }

            $grand = 0;
            $count = 0;

            foreach ($picked as $v) {
                // Pastikan ada stok awal di gudang terkait (supaya gerak stok terasa realistis)
                $stockWh = match ($tx->type) {
                    'penjualan' => $tx->source_warehouse_id ?? Warehouse::query()->inRandomOrder()->value('id'),
                    'pemindahan' => $tx->source_warehouse_id,
                    'pengembalian' => $tx->destination_warehouse_id ?? ($tx->source_warehouse_id ?? Warehouse::query()->inRandomOrder()->value('id')),
                    default => Warehouse::query()->inRandomOrder()->value('id'),
                };

                if (!$stockWh)
                    $stockWh = Warehouse::factory()->create()->id;
                WarehouseVariantStock::firstOrCreate(
                    ['warehouse_id' => $stockWh, 'product_variant_id' => $v->id],
                    ['qty' => random_int(10, 120), 'reserved_qty' => 0]
                );

                $qty = random_int(1, 5);
                $price = (int) $v->price;
                $disc = random_int(0, 100) < 25 ? random_int(0, (int) round($price * 0.15)) : 0;
                $total = $qty * max(0, $price - $disc);

                TransactionDetail::create([
                    'transaction_id' => $tx->id,
                    'product_id' => $v->product_id,
                    'product_variant_id' => $v->id,
                    'warehouse_id' => null, // gunakan gudang header; bisa diisi jika mau override
                    'qty' => $qty,
                    'price' => $price,
                    'discount_amount' => $disc,
                    'line_total' => $total,
                ]);

                $grand += $total;
                $count++;

                // Jika posted, catat movements & adjust stok minimal
                if ($tx->status === 'posted') {
                    $occur = Carbon::parse($tx->posted_at ?? $tx->transaction_date)->toDateTimeString();

                    if ($tx->type === 'penjualan') {
                        // OUT dari source
                        $fromWh = $tx->source_warehouse_id ?? $stockWh;
                        InventoryMovement::create([
                            'transaction_id' => $tx->id,
                            'from_warehouse_id' => $fromWh,
                            'to_warehouse_id' => null,
                            'product_variant_id' => $v->id,
                            'qty_change' => -$qty,
                            'type' => 'out',
                            'occurred_at' => $occur,
                            'remarks' => 'Penjualan',
                        ]);
                        $st = WarehouseVariantStock::firstOrCreate(
                            ['warehouse_id' => $fromWh, 'product_variant_id' => $v->id],
                            ['qty' => 0, 'reserved_qty' => 0]
                        );
                        $st->qty = max(0, (int) $st->qty - $qty);
                        $st->save();
                    } elseif ($tx->type === 'pemindahan') {
                        // OUT source, IN dest
                        InventoryMovement::create([
                            'transaction_id' => $tx->id,
                            'from_warehouse_id' => $tx->source_warehouse_id,
                            'to_warehouse_id' => null,
                            'product_variant_id' => $v->id,
                            'qty_change' => -$qty,
                            'type' => 'out',
                            'occurred_at' => $occur,
                            'remarks' => 'Transfer keluar',
                        ]);
                        InventoryMovement::create([
                            'transaction_id' => $tx->id,
                            'from_warehouse_id' => null,
                            'to_warehouse_id' => $tx->destination_warehouse_id,
                            'product_variant_id' => $v->id,
                            'qty_change' => $qty,
                            'type' => 'in',
                            'occurred_at' => $occur,
                            'remarks' => 'Transfer masuk',
                        ]);
                        $out = WarehouseVariantStock::firstOrCreate(
                            ['warehouse_id' => $tx->source_warehouse_id, 'product_variant_id' => $v->id],
                            ['qty' => 0, 'reserved_qty' => 0]
                        );
                        $in = WarehouseVariantStock::firstOrCreate(
                            ['warehouse_id' => $tx->destination_warehouse_id, 'product_variant_id' => $v->id],
                            ['qty' => 0, 'reserved_qty' => 0]
                        );
                        $out->qty = max(0, (int) $out->qty - $qty);
                        $in->qty = (int) $in->qty + $qty;
                        $out->save();
                        $in->save();
                    } else { // pengembalian
                        $toWh = $tx->destination_warehouse_id ?? $stockWh;
                        InventoryMovement::create([
                            'transaction_id' => $tx->id,
                            'from_warehouse_id' => null,
                            'to_warehouse_id' => $toWh,
                            'product_variant_id' => $v->id,
                            'qty_change' => $qty,
                            'type' => 'in',
                            'occurred_at' => $occur,
                            'remarks' => 'Retur masuk',
                        ]);
                        $st = WarehouseVariantStock::firstOrCreate(
                            ['warehouse_id' => $toWh, 'product_variant_id' => $v->id],
                            ['qty' => 0, 'reserved_qty' => 0]
                        );
                        $st->qty = (int) $st->qty + $qty;
                        $st->save();
                    }
                }
            }

            // Ringkasan header
            $tx->item_count = $count;
            $tx->grand_total = $grand;
            if ($tx->status === 'posted' && !$tx->posted_at) {
                $tx->posted_at = Carbon::parse($tx->transaction_date)->addHour();
            }
            $tx->save();

            // Invoice & Payment: opsional, tidak harus semua
            if ($tx->status === 'posted' && $tx->type === 'penjualan' && $tx->grand_total > 0) {
                $inv = \Database\Factories\Inventory\InvoiceFactory::new()->for($tx)->create([
                    'subtotal' => $tx->grand_total,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'shipping_fee' => 0,
                    'total_amount' => $tx->grand_total,
                    'paid_amount' => 0,
                    'is_paid' => false,
                    'issued_at' => $tx->posted_at,
                    'due_at' => Carbon::parse($tx->posted_at)->addDays(7),
                ]);

                // 50% langsung lunas
                if (random_int(0, 100) < 50) {
                    \Database\Factories\Inventory\PaymentFactory::new()->for($inv)->create([
                        'amount' => $tx->grand_total,
                        'method' => 'transfer',
                        'paid_at' => Carbon::parse($tx->posted_at)->addHours(2),
                        'notes' => 'Auto paid',
                    ]);
                    $inv->paid_amount = $tx->grand_total;
                    $inv->is_paid = true;
                    $inv->save();
                }
            }
        });
    }
}
