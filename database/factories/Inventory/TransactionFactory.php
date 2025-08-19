<?php

namespace Database\Factories\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_number' => strtoupper($this->faker->unique()->bothify('TRX-#####')),
            'type' => $this->faker->randomElement(['penjualan', 'dropship', 'pengiriman', 'penerimaan']),
            'transaction_date' => $this->faker->date(),
            'customer_name' => $this->faker->name(),
            'shipping_address' => $this->faker->address(),
        ];
    }
}
