<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $total = $this->faker->randomFloat(2, 100000, 2000000);
        $paid = $this->faker->randomFloat(2, 0, $total);

        return [
            'transaction_id' => Transaction::factory(),
            'invoice_number' => strtoupper($this->faker->unique()->bothify('INV-#####')),
            'total_amount' => $total,
            'paid_amount' => $paid,
            'is_paid' => $paid >= $total,
        ];
    }
}
