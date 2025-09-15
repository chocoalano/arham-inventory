<?php

namespace Database\Factories\Finance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
class FiscalYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Tahun fiskal unik & wajar (±2 tahun dari sekarang)
        $year = (int) now()->year;

        return [
            'year'      => $this->faker->unique()->numberBetween($year - 1, $year + 1),
            'starts_on' => fn (array $attrs) => Carbon::createFromDate($attrs['year'], 1, 1)->toDateString(),
            'ends_on'   => fn (array $attrs) => Carbon::createFromDate($attrs['year'], 12, 31)->toDateString(),
            'is_closed' => false,
        ];
    }

    public function closed(): self
    {
        return $this->state(fn (array $attrs) => ['is_closed' => true]);
    }
}
