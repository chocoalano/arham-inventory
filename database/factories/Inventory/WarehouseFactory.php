<?php

namespace Database\Factories\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'lat' => $this->faker->latitude(-10, 10),
            'lng' => $this->faker->longitude(95, 141),
            'location' => $this->faker->address(),
        ];
    }
}
