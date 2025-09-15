<?php

namespace Database\Seeders\Accounting;

use App\Models\Finance\CostCenter;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'CC-001', 'name' => 'Head Office'],
            ['code' => 'CC-002', 'name' => 'Production'],
            ['code' => 'CC-003', 'name' => 'Sales & Marketing'],
        ];

        foreach ($items as $it) {
            CostCenter::query()->firstOrCreate(
                ['code' => $it['code']],
                ['name' => $it['name'], 'is_active' => true]
            );
        }
    }
}
