<?php

namespace Database\Factories\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'name' => $this->faker->words(3, true),
            'model' => strtoupper($this->faker->bothify('MDL-###')),
            'description' => $this->faker->sentence(),
        ];
    }
}
