<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\Invoice;
use App\Models\Inventory\Payment;
use App\Models\Inventory\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $issued = $this->faker->dateTimeBetween('-20 days', 'now');
        $subtotal = $this->faker->numberBetween(100_000, 5_000_000);

        return [
            'transaction_id' => Transaction::factory()->posted()->sale()->withAutoDetails()->create()->id,
            'invoice_number' => strtoupper('INV-' . Carbon::instance($issued)->format('Ymd') . '-' . Str::upper(Str::random(5))),
            'issued_at'      => Carbon::instance($issued)->toDateTimeString(),
            'due_at'         => Carbon::instance($issued)->modify('+7 days')->toDateTimeString(),
            'subtotal'       => $subtotal,
            'discount_total' => 0,
            'tax_total'      => 0,
            'shipping_fee'   => 0,
            'total_amount'   => $subtotal,
            'paid_amount'    => 0,
            'is_paid'        => false,
        ];
    }

    public function paid(): self
    {
        return $this->afterCreating(function (Invoice $inv) {
            Payment::factory()->for($inv)->create([
                'amount' => $inv->total_amount,
                'method' => 'transfer',
                'paid_at'=> Carbon::parse($inv->issued_at)->addDay(),
            ]);
            $inv->paid_amount = $inv->total_amount;
            $inv->is_paid     = true;
            $inv->save();
        });
    }
}
