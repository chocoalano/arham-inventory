<?php

namespace Database\Seeders;

// Gunakan model seeder yang telah Anda buat
use App\Models\User;
use Database\Seeders\Accounting\AccountMappingSeeder;
use Database\Seeders\Accounting\ChartOfAccountsSeeder;
use Database\Seeders\Accounting\CostCenterSeeder;
use Database\Seeders\Accounting\FiscalCalendarSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seeder aplikasi.
     */
    public function run(): void
    {
        // Panggil seeder lain yang dibutuhkan di sini.
        // Seeder ini akan membuat role, permission, dan user dummy.
        $this->call([
            RolePermissionSeeder::class,
            // InventorySeeder::class,
            // FiscalCalendarSeeder::class,
            // ChartOfAccountsSeeder::class,
            // AccountMappingSeeder::class,
            // CostCenterSeeder::class,
            // AccountingDemoSeeder::class,
        ]);
    }
}
