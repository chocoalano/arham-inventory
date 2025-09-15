<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
class AccountMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Kunci umum (pastikan unik di seeder saat insert massal)
        $keys = [
            'sales_revenue', 'cogs', 'inventory', 'ar', 'ap',
            'tax_output', 'tax_input', 'shipping_income'
        ];

        return [
            'key'        => $this->faker->unique()->randomElement($keys),
            'account_id' => Account::factory(),
        ];
    }
}
