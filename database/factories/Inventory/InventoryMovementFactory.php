<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['in','out']);
        $from = $type === 'out' ? Warehouse::factory() : null;
        $to   = $type === 'in'  ? Warehouse::factory() : null;

        $dt = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'transaction_id'     => $this->faker->optional(0.7)->passthrough(Transaction::factory()),
            'from_warehouse_id'  => $from,
            'to_warehouse_id'    => $to,
            'product_variant_id' => ProductVariant::factory(),
            'qty_change'         => $type === 'out' ? -1 * $this->faker->numberBetween(1, 10) : $this->faker->numberBetween(1, 10),
            'type'               => $type,
            'occurred_at'        => Carbon::instance($dt)->toDateTimeString(),
            'remarks'            => $this->faker->optional()->sentence(),
            'created_by'         => null,
        ];
    }
}
