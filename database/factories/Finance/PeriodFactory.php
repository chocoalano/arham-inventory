<?php

namespace Database\Factories\Finance;

use App\Models\Finance\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
class PeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fiscal_year_id' => FiscalYear::factory(),
            'period_no'      => $this->faker->numberBetween(1, 12), // 13 reserved for adjustment
            'starts_on'      => function (array $attrs) {
                $fy = FiscalYear::find($attrs['fiscal_year_id']) ?? FiscalYear::factory()->create();
                $month = (int) $attrs['period_no'];
                return Carbon::createFromDate($fy->year, $month, 1)->toDateString();
            },
            'ends_on'        => function (array $attrs) {
                $fy = FiscalYear::find($attrs['fiscal_year_id']) ?? FiscalYear::factory()->create();
                $month = (int) $attrs['period_no'];
                return Carbon::createFromDate($fy->year, $month, 1)->endOfMonth()->toDateString();
            },
            'is_closed'      => false,
        ];
    }

    public function adjustment13(): self
    {
        return $this->state(function (array $attrs) {
            $fy = FiscalYear::find($attrs['fiscal_year_id']) ?? FiscalYear::factory()->create();
            return [
                'period_no' => 13,
                // Adjustment biasanya di hari terakhir tahun fiskal
                'starts_on' => Carbon::createFromDate($fy->year, 12, 31)->toDateString(),
                'ends_on'   => Carbon::createFromDate($fy->year, 12, 31)->toDateString(),
            ];
        });
    }

    public function closed(): self
    {
        return $this->state(['is_closed' => true]);
    }
}
