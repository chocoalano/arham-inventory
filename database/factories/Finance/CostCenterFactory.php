<?php

namespace Database\Factories\Finance;

use Illuminate\Database\Eloquent\Factories\Factory;
class CostCenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'      => strtoupper('CC-' . $this->faker->unique()->numerify('###')),
            'name'      => 'Cost Center ' . $this->faker->unique()->word(),
            'is_active' => true,
        ];
    }
}
