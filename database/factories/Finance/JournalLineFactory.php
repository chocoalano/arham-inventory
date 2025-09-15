<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CostCenter;
use App\Models\Finance\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;
class JournalLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isDebit = $this->faker->boolean(50);
        $amount  = $this->faker->numberBetween(10_000, 5_000_000);

        return [
            'journal_id'     => Journal::factory(),
            'account_id'     => Account::factory(),
            'cost_center_id' => $this->faker->optional(0.4)->randomElement([null, CostCenter::factory()]),
            'description'    => $this->faker->sentence(4),
            'debit'          => $isDebit ? $amount : 0,
            'credit'         => $isDebit ? 0 : $amount,
            'currency'       => $this->faker->optional(0.15)->randomElement(['USD','EUR','SGD']),
            'fx_rate'        => null, // akan diisi di seeder jika currency != null
        ];
    }
}
