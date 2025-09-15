<?php

namespace Database\Factories\Finance;

use Illuminate\Database\Eloquent\Factories\Factory;
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Tipe utama → prefix nomor akun yang umum
        $types = [
            'asset'     => '1',
            'liability' => '2',
            'equity'    => '3',
            'revenue'   => '4',
            'expense'   => '5',
        ];

        $type = $this->faker->randomElement(array_keys($types));
        $number = $types[$type] . $this->faker->unique()->numerify('###'); // ex: 1101/2101/...

        // Subtype contoh ringan (opsional)
        $subtypes = [
            'asset'     => ['cash', 'ar', 'inventory', 'prepaid', 'fixed_asset'],
            'liability' => ['ap', 'tax_payable', 'accrual'],
            'equity'    => ['capital', 'retained_earnings'],
            'revenue'   => ['sales', 'other_income'],
            'expense'   => ['cogs', 'salary', 'rent', 'utilities', 'marketing'],
        ];

        return [
            'number'      => $number,
            'name'        => ucfirst($type) . ' ' . $this->faker->words(2, true),
            'type'        => $type,
            'subtype'     => $this->faker->optional(0.7)->randomElement($subtypes[$type]),
            'is_postable' => true,
            'is_active'   => true,
        ];
    }

    public function nonPostable(): self
    {
        return $this->state(['is_postable' => false]);
    }
}
