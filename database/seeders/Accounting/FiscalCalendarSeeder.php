<?php

namespace Database\Seeders\Accounting;

use App\Models\Finance\FiscalYear;
use App\Models\Finance\Period;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FiscalCalendarSeeder extends Seeder
{
    public function run(): void
    {
        // Buat 1 tahun fiskal aktif (tahun berjalan)
        $fy = FiscalYear::query()->firstOrCreate(
            ['year' => now()->year],
            [
                'starts_on' => now()->startOfYear()->toDateString(),
                'ends_on'   => now()->endOfYear()->toDateString(),
                'is_closed' => false,
            ]
        );

        // 12 periode bulanan
        for ($m = 1; $m <= 12; $m++) {
            Period::query()->firstOrCreate(
                ['fiscal_year_id' => $fy->id, 'period_no' => $m],
                [
                    'starts_on' => Carbon::createFromDate($fy->year, $m, 1)->toDateString(),
                    'ends_on'   => Carbon::createFromDate($fy->year, $m, 1)->endOfMonth()->toDateString(),
                    'is_closed' => false,
                ]
            );
        }

        // Opsional: period ke-13 untuk adjustment
        // Period::factory()->for($fy)->adjustment13()->create();
    }
}
