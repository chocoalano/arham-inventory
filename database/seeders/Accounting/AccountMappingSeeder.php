<?php

namespace Database\Seeders\Accounting;

use App\Models\Finance\Account;
use App\Models\Finance\AccountMapping;
use Illuminate\Database\Seeder;

class AccountMappingSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'sales_revenue' => '4101',
            'cogs'          => '5101',
            'inventory'     => '1301',
            'ar'            => '1201',
            'ap'            => '2101',
            'tax_output'    => '2201',
            'tax_input'     => '2201',
            'shipping_income' => '4101',
        ];

        foreach ($map as $key => $number) {
            $account = Account::where('number', $number)->first();
            if (!$account) { continue; }

            AccountMapping::query()->updateOrCreate(
                ['key' => $key],
                ['account_id' => $account->id]
            );
        }
    }
}
