<?php

namespace Database\Seeders;

use App\Models\Inventory\Invoice;
use App\Models\Inventory\Payment;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductImage;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Stocks;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\TransactionDetail;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- 1) Warehouses ---
        $warehouses = Warehouse::factory()->count(4)->create();

        // --- 2) Products + Variants + Images ---
        // Buat 15 produk; tiap produk 2-5 varian dan 1-3 gambar
        Product::factory()
            ->count(15)
            ->create()
            ->each(function (Product $product) {
                // Images
                ProductImage::factory()
                    ->count(fake()->numberBetween(1, 3))
                    ->create([
                        'product_id' => $product->id,
                    ]);

                // Variants
                ProductVariant::factory()
                    ->count(fake()->numberBetween(2, 5))
                    ->create([
                        'product_id' => $product->id,
                    ]);
            });

        // Kumpulkan semua varian yang sudah ada
        $variants = ProductVariant::all();

        // --- 3) Stocks: tiap varian punya stok di SEMUA gudang ---
        foreach ($variants as $variant) {
            foreach ($warehouses as $wh) {
                Stocks::factory()->create([
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $wh->id,
                    'quantity' => fake()->numberBetween(5, 120),
                ]);
            }
        }

        // --- 4) Transactions + Details + Invoices + Payments ---
        // Buat 30 transaksi acak, tiap transaksi 1–5 item
        Transaction::factory()
            ->count(30)
            ->create()
            ->each(function (Transaction $trx) use ($variants, $warehouses) {
                $detailsCount = fake()->numberBetween(1, 5);
                $total = 0;

                for ($i = 0; $i < $detailsCount; $i++) {
                    $variant = $variants->random();
                    // Ambil stok acak untuk varian tsb di salah satu gudang
                    $wh = $warehouses->random();
                    $price = fake()->randomFloat(2, 50000, 500000);
                    // batasi qty agar tidak berlebihan (opsional)
                    $qty = fake()->numberBetween(1, 10);

                    TransactionDetail::factory()->create([
                        'transaction_id' => $trx->id,
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $wh->id,
                        'quantity' => $qty,
                        'price' => $price,
                    ]);

                    $total += $qty * $price;
                }

                // Buat Invoice untuk transaksi ini
                $invoice = Invoice::factory()->create([
                    'transaction_id' => $trx->id,
                    'total_amount' => $total,
                    // paid_amount & is_paid akan disesuaikan setelah bikin payments
                    'paid_amount' => 0,
                    'is_paid' => false,
                ]);

                // Buat 0–3 pembayaran, jumlahnya tidak melebihi total
                $paymentsCount = fake()->numberBetween(0, 3);
                $remaining = $total;

                for ($j = 0; $j < $paymentsCount; $j++) {
                    if ($remaining <= 0)
                        break;

                    // Distribusikan pembayaran secara acak
                    $amount = round(
                        $j === $paymentsCount - 1
                        ? $remaining
                        : min($remaining, fake()->randomFloat(2, 20000, max(20000, $total / 2))),
                        2
                    );

                    if ($amount <= 0)
                        continue;

                    Payment::factory()->create([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'method' => fake()->randomElement(['transfer', 'cash', 'card']),
                        'payment_date' => fake()->date(),
                        'notes' => fake()->optional()->sentence(),
                    ]);

                    $remaining -= $amount;
                }

                // Update paid_amount & is_paid sesuai payments yang dibuat
                $paid = $invoice->payments()->sum('amount');
                $invoice->update([
                    'paid_amount' => $paid,
                    'is_paid' => $paid >= $total,
                ]);
            });
    }
}
