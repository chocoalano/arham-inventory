<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseVariantStock;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseVariantStockFactory extends Factory
{
    protected $model = WarehouseVariantStock::class;

    public function definition(): array
    {
        $qty = $this->faker->numberBetween(0, 500);
        return [
            'warehouse_id'       => Warehouse::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'qty'                => $qty,
            'reserved_qty'       => $this->faker->numberBetween(0, (int) floor($qty / 3)),
        ];
    }
}
