<?php

namespace Database\Seeders;
use App\Models\Finance\Account;
use App\Models\Finance\CostCenter;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalLine;
use App\Models\Finance\Period;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AccountingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fy = FiscalYear::orderByDesc('year')->first();
        if (!$fy) { return; }

        $periods = Period::where('fiscal_year_id', $fy->id)->orderBy('period_no')->get();
        if ($periods->isEmpty()) { return; }

        $assets     = Account::where('type','asset')->where('is_postable', true)->pluck('id')->all();
        $liabs      = Account::where('type','liability')->where('is_postable', true)->pluck('id')->all();
        $equities   = Account::where('type','equity')->where('is_postable', true)->pluck('id')->all();
        $revenues   = Account::where('type','revenue')->where('is_postable', true)->pluck('id')->all();
        $expenses   = Account::where('type','expense')->where('is_postable', true)->pluck('id')->all();

        $costCenters = CostCenter::pluck('id')->all();

        // Helper: pilih acak aman
        $pick = function(array $arr) {
            return $arr[array_rand($arr)];
        };

        // Buat 60 jurnal (5 per bulan x 12) dengan sebagian "posted"
        foreach ($periods as $p) {
            $perMonth = 5;
            for ($i=0; $i<$perMonth; $i++) {
                $date = Carbon::parse($p->starts_on)->addDays(random_int(0, max(0, Carbon::parse($p->ends_on)->diffInDays($p->starts_on))));
                $j = Journal::factory()->create([
                    'journal_date' => $date->toDateString(),
                    'period_id'    => $p->id,
                    'status'       => $i < 4 ? 'posted' : 'draft', // rata-rata 80% posted
                    'posted_at'    => $i < 4 ? $date->copy()->setTime(17,0)->toDateTimeString() : null,
                ]);

                // Bentuk pola transaksi sederhana & seimbang:
                // 1) Penjualan tunai: Dr Cash / Cr Sales ( + opsi tax )
                if (random_int(0, 1) === 1 && !empty($assets) && !empty($revenues)) {
                    $amount = random_int(100_000, 5_000_000);
                    $taxPct = (random_int(0, 1) === 1) ? 0.11 : 0.0;
                    $tax    = intval(round($amount * $taxPct));
                    $gross  = $amount + $tax;

                    JournalLine::create([
                        'journal_id'     => $j->id,
                        'account_id'     => $pick($assets), // Cash/Bank
                        'cost_center_id' => random_int(0, 100) < 40 && !empty($costCenters) ? $pick($costCenters) : null,
                        'description'    => 'Penjualan tunai',
                        'debit'          => $gross,
                        'credit'         => 0,
                        'currency'       => null,
                        'fx_rate'        => null,
                    ]);

                    JournalLine::create([
                        'journal_id'     => $j->id,
                        'account_id'     => $pick($revenues), // Sales
                        'description'    => 'Pendapatan penjualan',
                        'debit'          => 0,
                        'credit'         => $amount,
                        'currency'       => null,
                        'fx_rate'        => null,
                    ]);

                    if ($tax > 0 && !empty($liabs)) {
                        JournalLine::create([
                            'journal_id'  => $j->id,
                            'account_id'  => $pick($liabs), // Pajak keluaran → liability
                            'description' => 'PPN Keluaran',
                            'debit'       => 0,
                            'credit'      => $tax,
                        ]);
                    }
                }
                // 2) Beban operasional: Dr Expense / Cr Cash/AP
                else if (!empty($expenses) && (!empty($assets) || !empty($liabs))) {
                    $amount = random_int(50_000, 2_000_000);

                    JournalLine::create([
                        'journal_id'     => $j->id,
                        'account_id'     => $pick($expenses),
                        'cost_center_id' => random_int(0, 100) < 60 && !empty($costCenters) ? $pick($costCenters) : null,
                        'description'    => 'Beban operasional',
                        'debit'          => $amount,
                        'credit'         => 0,
                    ]);

                    $creditAccountPool = array_merge($assets, $liabs);
                    JournalLine::create([
                        'journal_id'  => $j->id,
                        'account_id'  => $pick($creditAccountPool),
                        'description' => 'Pembayaran/Utang',
                        'debit'       => 0,
                        'credit'      => $amount,
                    ]);
                }

                // (Opsional) Tambahkan 1-2 baris kecil untuk variasi lalu **rebalance** agar tetap seimbang
                $extraLines = random_int(0, 1);
                for ($k=0; $k<$extraLines; $k++) {
                    $isDebit = (bool) random_int(0,1);
                    $amt     = random_int(10_000, 200_000);
                    $accPool = $isDebit ? array_merge($assets, $expenses) : array_merge($revenues, $liabs, $equities);
                    if (empty($accPool)) { continue; }

                    JournalLine::create([
                        'journal_id'  => $j->id,
                        'account_id'  => $pick($accPool),
                        'description' => 'Penyesuaian kecil',
                        'debit'       => $isDebit ? $amt : 0,
                        'credit'      => $isDebit ? 0 : $amt,
                    ]);
                }

                // Rebalance: pastikan total debit == total credit
                $totals = JournalLine::where('journal_id', $j->id)
                    ->selectRaw('SUM(debit) as d, SUM(credit) as c')
                    ->first();

                $d = (int) ($totals->d ?? 0);
                $c = (int) ($totals->c ?? 0);

                if ($d !== $c) {
                    $diff = abs($d - $c);
                    // Pilih akun equity atau suspense (kalau ada) untuk balancing
                    $balAccPool = !empty($equities) ? $equities : (!empty($assets) ? $assets : $liabs);
                    $balAcc = $pick($balAccPool);

                    JournalLine::create([
                        'journal_id'  => $j->id,
                        'account_id'  => $balAcc,
                        'description' => 'Auto-balance',
                        'debit'       => $d < $c ? $diff : 0,
                        'credit'      => $d > $c ? $diff : 0,
                    ]);
                }
            }
        }
    }
}
