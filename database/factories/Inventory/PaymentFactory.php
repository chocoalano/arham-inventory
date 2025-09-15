<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $inv  = Invoice::factory()->create();
        $dt   = Carbon::parse($inv->issued_at ?? now())->addDays(rand(0, 5));

        return [
            'invoice_id'  => $inv->id,
            'amount'      => $this->faker->numberBetween(50_000, (int) $inv->total_amount),
            'method'      => $this->faker->randomElement(['transfer','cash','card','e-wallet']),
            'reference_no'=> $this->faker->optional()->bothify('REF-########'),
            'paid_at'     => $dt->toDateTimeString(),
            'notes'       => $this->faker->optional()->sentence(),
            'received_by' => null,
        ];
    }
}
