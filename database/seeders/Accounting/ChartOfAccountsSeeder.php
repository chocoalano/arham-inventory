<?php

namespace Database\Seeders\Accounting;

use App\Models\Finance\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // Contoh daftar akun minimal dan terarah agar laporan mudah dipahami
        $accounts = [
            // Assets
            ['number'=>'1101','name'=>'Cash on Hand','type'=>'asset','subtype'=>'cash'],
            ['number'=>'1102','name'=>'Cash in Bank','type'=>'asset','subtype'=>'cash'],
            ['number'=>'1201','name'=>'Accounts Receivable','type'=>'asset','subtype'=>'ar'],
            ['number'=>'1301','name'=>'Inventory','type'=>'asset','subtype'=>'inventory'],

            // Liabilities
            ['number'=>'2101','name'=>'Accounts Payable','type'=>'liability','subtype'=>'ap'],
            ['number'=>'2201','name'=>'Tax Payable','type'=>'liability','subtype'=>'tax_payable'],

            // Equity
            ['number'=>'3101','name'=>'Owner Capital','type'=>'equity','subtype'=>'capital'],
            ['number'=>'3201','name'=>'Retained Earnings','type'=>'equity','subtype'=>'retained_earnings'],

            // Revenue
            ['number'=>'4101','name'=>'Sales Revenue','type'=>'revenue','subtype'=>'sales'],

            // Expense
            ['number'=>'5101','name'=>'COGS','type'=>'expense','subtype'=>'cogs'],
            ['number'=>'5201','name'=>'Salary Expense','type'=>'expense','subtype'=>'salary'],
            ['number'=>'5202','name'=>'Rent Expense','type'=>'expense','subtype'=>'rent'],
        ];

        foreach ($accounts as $a) {
            Account::query()->firstOrCreate(
                ['number' => $a['number']],
                $a + ['is_postable' => true, 'is_active' => true]
            );
        }
    }
}
