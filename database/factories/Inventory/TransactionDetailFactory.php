<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionDetailFactory extends Factory
{
    public function definition(): array
    {
        $variant = ProductVariant::factory()->for(Product::factory())->create();

        $qty   = $this->faker->numberBetween(1, 5);
        $price = (int) $variant->price;
        $disc  = $this->faker->boolean(20) ? $this->faker->numberBetween(0, (int) round($price * 0.15)) : 0;

        return [
            'transaction_id'     => Transaction::factory(),
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'warehouse_id'       => null,
            'qty'                => $qty,
            'price'              => $price,
            'discount_amount'    => $disc,
            'line_total'         => $qty * max(0, $price - $disc),
        ];
    }
}
