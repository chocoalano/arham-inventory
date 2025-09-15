<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
class ProductAccountLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'product_id' WAJIB diset di seeder jika tabel products tersedia.
            'product_id'          => null,
            'inventory_account_id'=> Account::factory(),
            'cogs_account_id'     => Account::factory(),
            'sales_account_id'    => Account::factory(),
        ];
    }
}
