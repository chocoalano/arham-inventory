<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount' => $this->faker->randomFloat(2, 50000, 1000000),
            'method' => $this->faker->randomElement(['transfer', 'cash', 'card']),
            'payment_date' => $this->faker->date(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
