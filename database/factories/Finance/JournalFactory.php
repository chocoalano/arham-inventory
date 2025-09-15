<?php

namespace Database\Factories\Finance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
class JournalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-3 months', '+0 days');
        $journalNo = 'JRN-' . Carbon::instance($date)->format('Ymd') . '-' . strtoupper(Str::random(4));

        return [
            'journal_no'  => $journalNo,
            'journal_date'=> Carbon::instance($date)->toDateString(),
            'period_id'   => null, // akan diset di seeder biar sesuai tanggal
            'source_type' => $this->faker->optional()->randomElement(['sales_order', 'purchase_bill', 'stock_adjustment']),
            'source_id'   => $this->faker->optional()->numberBetween(1, 5000),
            'status'      => $this->faker->randomElement(['draft', 'posted']), // sebagian posted untuk laporan
            'remarks'     => $this->faker->optional()->sentence(),
            'created_by'  => null, // opsional: set jika ada tabel users
            'posted_by'   => null,
            'posted_at'   => null,
        ];
    }

    public function posted(): self
    {
        return $this->state(function (array $attrs) {
            $dt = Carbon::parse($attrs['journal_date'] ?? now());
            return [
                'status'   => 'posted',
                'posted_at'=> $dt->copy()->setTime(17, 0, 0)->toDateTimeString(),
            ];
        });
    }

    public function draft(): self
    {
        return $this->state(['status' => 'draft', 'posted_at' => null, 'posted_by' => null]);
    }

    public function void(): self
    {
        return $this->state(['status' => 'void', 'posted_at' => null, 'posted_by' => null]);
    }
}
