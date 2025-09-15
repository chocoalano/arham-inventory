<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'image_path' => $this->faker->imageUrl(800, 800, 'food', true, 'product'),
            'is_primary' => false,
            'sort_order' => $this->faker->numberBetween(0, 9),
        ];
    }

    public function primary(): self
    {
        return $this->state(['is_primary' => true, 'sort_order' => 0]);
    }
}
