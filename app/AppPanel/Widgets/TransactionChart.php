<?php

namespace App\AppPanel\Widgets;

use App\Models\Inventory\Invoice;
use App\Models\Inventory\Transaction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransactionChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Grafik Transaksi';

    // =======================
    // KONFIGURASI
    // =======================
    protected string $trxTypeCol = 'type';   // kolom tipe transaksi
    protected string $trxTotalCol = 'total';  // nominal transaksi

    // nilai tipe transaksi (SAMA persis dengan yang ada di DB kamu)
    protected string $saleTypeVal = 'penjualan';
    protected string $transferTypeVal = 'pemindahan';
    protected string $returnTypeVal = 'pengembalian';

    protected string $invoiceTotalCol = 'total_amount'; // fallback invoice (penjualan saja)

    protected function getFilters(): ?array
    {
        return [
            '7d' => '7 Hari',
            '30d' => '30 Hari',
            '12m' => '12 Bulan',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? '7d';
        $useTransactions = $this->canUseTransactions();

        if ($filter === '12m') {
            [$labels, $sales, $transfers, $returns] = $useTransactions
                ? $this->seriesMonthlyFromTransactions(12)
                : $this->seriesMonthlyFromInvoices(12);
        } else {
            $days = $filter === '30d' ? 30 : 7;

            [$labels, $sales, $transfers, $returns] = $useTransactions
                ? $this->seriesDailyFromTransactions($days)
                : $this->seriesDailyFromInvoices($days);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Penjualan',
                    'data' => $sales,
                    'borderColor' => '#10b981', // hijau
                    'backgroundColor' => '#10b98133',
                    'tension' => 0.3,
                    'pointRadius' => 2,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Pemindahan',
                    'data' => $transfers,
                    'borderColor' => '#f59e0b', // oranye
                    'backgroundColor' => '#f59e0b33',
                    'tension' => 0.3,
                    'pointRadius' => 2,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Pengembalian',
                    'data' => $returns,
                    'borderColor' => '#ef4444', // merah
                    'backgroundColor' => '#ef444433',
                    'tension' => 0.3,
                    'pointRadius' => 2,
                    'borderWidth' => 2,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function canUseTransactions(): bool
    {
        $trxTable = (new Transaction)->getTable();
        return Schema::hasColumn($trxTable, $this->trxTypeCol)
            && Schema::hasColumn($trxTable, $this->trxTotalCol);
    }

    /**
     * DAILY: [labels, penjualan[], pemindahan[], pengembalian[]]
     */
    protected function seriesDailyFromTransactions(int $days): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);
        $period = CarbonPeriod::create($start, $end);

        $trxTable = (new Transaction)->getTable();

        // Ambil hanya 3 tipe yang kamu pakai
        $types = [$this->saleTypeVal, $this->transferTypeVal, $this->returnTypeVal];

        $rows = DB::table($trxTable)
            ->selectRaw('DATE(created_at) as d, ' . $this->trxTypeCol . ' as t, SUM(' . $this->trxTotalCol . ') as s')
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->whereIn($this->trxTypeCol, $types)
            ->groupBy('d', 't')
            ->get()
            ->groupBy('d');

        $labels = [];
        $sales = [];
        $transfers = [];
        $returns = [];

        foreach ($period as $date) {
            $key = $date->toDateString();
            $dayRows = $rows->get($key, collect());

            $saleSum = (float) ($dayRows->firstWhere('t', $this->saleTypeVal)->s ?? 0);
            $transferSum = (float) ($dayRows->firstWhere('t', $this->transferTypeVal)->s ?? 0);
            $returnSum = (float) ($dayRows->firstWhere('t', $this->returnTypeVal)->s ?? 0);

            $labels[] = $date->format('d M');
            $sales[] = round($saleSum, 2);
            $transfers[] = round($transferSum, 2);
            $returns[] = round($returnSum, 2);
        }

        return [$labels, $sales, $transfers, $returns];
    }

    /**
     * DAILY FALLBACK (Invoices → hanya penjualan)
     */
    protected function seriesDailyFromInvoices(int $days): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);
        $period = CarbonPeriod::create($start, $end);

        $labels = [];
        $sales = [];
        $transfers = [];
        $returns = [];

        $rows = Invoice::selectRaw('DATE(created_at) as d, SUM(' . $this->invoiceTotalCol . ') as s')
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy('d')
            ->pluck('s', 'd');

        foreach ($period as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('d M');
            $sales[] = round((float) ($rows[$key] ?? 0), 2);
            $transfers[] = 0.0;
            $returns[] = 0.0;
        }

        return [$labels, $sales, $transfers, $returns];
    }

    /**
     * MONTHLY: 12 bulan terakhir
     */
    protected function seriesMonthlyFromTransactions(int $months): array
    {
        $end = Carbon::now()->startOfMonth();
        $start = $end->copy()->subMonths($months - 1);

        $trxTable = (new Transaction)->getTable();
        $types = [$this->saleTypeVal, $this->transferTypeVal, $this->returnTypeVal];

        $rows = DB::table($trxTable)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, ' . $this->trxTypeCol . ' as t, SUM(' . $this->trxTotalCol . ') as s')
            ->whereBetween('created_at', [$start, $end->copy()->endOfMonth()])
            ->whereIn($this->trxTypeCol, $types)
            ->groupBy('ym', 't')
            ->get()
            ->groupBy('ym');

        $labels = [];
        $sales = [];
        $transfers = [];
        $returns = [];

        $cursor = $start->copy();
        while ($cursor <= $end) {
            $ym = $cursor->format('Y-m');
            $label = $cursor->isoFormat('MMM YYYY');
            $labels[] = $label;

            $monthRows = $rows->get($ym, collect());
            $saleSum = (float) ($monthRows->firstWhere('t', $this->saleTypeVal)->s ?? 0);
            $transferSum = (float) ($monthRows->firstWhere('t', $this->transferTypeVal)->s ?? 0);
            $returnSum = (float) ($monthRows->firstWhere('t', $this->returnTypeVal)->s ?? 0);

            $sales[] = round($saleSum, 2);
            $transfers[] = round($transferSum, 2);
            $returns[] = round($returnSum, 2);

            $cursor->addMonth();
        }

        return [$labels, $sales, $transfers, $returns];
    }

    /**
     * MONTHLY FALLBACK (Invoices → hanya penjualan)
     */
    protected function seriesMonthlyFromInvoices(int $months): array
    {
        $end = Carbon::now()->startOfMonth();
        $start = $end->copy()->subMonths($months - 1);

        $rows = Invoice::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, SUM(' . $this->invoiceTotalCol . ') as s')
            ->whereBetween('created_at', [$start, $end->copy()->endOfMonth()])
            ->groupBy('ym')
            ->pluck('s', 'ym');

        $labels = [];
        $sales = [];
        $transfers = [];
        $returns = [];

        $cursor = $start->copy();
        while ($cursor <= $end) {
            $ym = $cursor->format('Y-m');
            $labels[] = $cursor->isoFormat('MMM YYYY');
            $sales[] = round((float) ($rows[$ym] ?? 0), 2);
            $transfers[] = 0.0;
            $returns[] = 0.0;
            $cursor->addMonth();
        }

        return [$labels, $sales, $transfers, $returns];
    }
}
