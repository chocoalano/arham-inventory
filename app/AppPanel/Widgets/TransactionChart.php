<?php

namespace App\AppPanel\Widgets;

use App\Models\Inventory\Invoice;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\TransactionDetail;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransactionChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';
    protected ?string $heading = 'Grafik Transaksi';

    // ======================= KONFIGURASI =======================
    protected string $trxTypeCol       = 'type';          // kolom tipe transaksi pada transactions
    protected string $invoiceTotalCol  = 'total_amount';  // kolom total pada invoices

    // Nilai tipe transaksi (harus sama dengan nilai di DB)
    protected string $saleTypeVal     = 'penjualan';
    protected string $transferTypeVal = 'pemindahan';
    protected string $returnTypeVal   = 'pengembalian';

    protected function getFilters(): ?array
    {
        return [
            '7d'  => '7 Hari',
            '30d' => '30 Hari',
            '12m' => '12 Bulan',
        ];
    }

    protected function getData(): array
    {
        [$wid, $isSuperadmin] = $this->ctx();
        $filter = $this->filter ?? '7d';

        if ($filter === '12m') {
            [$labels, $sales, $transfers, $returns] =
                $this->seriesMonthly(12, $isSuperadmin ? null : $wid);
        } else {
            $days = $filter === '30d' ? 30 : 7;
            [$labels, $sales, $transfers, $returns] =
                $this->seriesDaily($days, $isSuperadmin ? null : $wid);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Penjualan',
                    'data' => $sales,
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b98133',
                    'tension' => 0.3,
                    'pointRadius' => 2,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Pemindahan',
                    'data' => $transfers,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b33',
                    'tension' => 0.3,
                    'pointRadius' => 2,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Pengembalian',
                    'data' => $returns,
                    'borderColor' => '#ef4444',
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

    /* =========================================================================
     |  Inti: daily & monthly series dengan filter gudang + fallback cerdas
     * ========================================================================= */

    protected function seriesDaily(int $days, ?int $wid): array
    {
        $end    = Carbon::today();
        $start  = $end->copy()->subDays($days - 1);
        $period = CarbonPeriod::create($start, $end);
        $labels = [];
        foreach ($period as $d) {
            $labels[] = $d->format('d M');
        }

        // 1) Ambil dari transactions (grand_total/total) bila kolom ada
        $trxCol = $this->resolveTransactionTotalColumn();
        $base   = $trxCol
            ? $this->seriesDailyFromTransactions($days, $wid, $trxCol)
            : ['labels' => $labels, 'sale' => array_fill(0, count($labels), 0.0), 'transfer' => array_fill(0, count($labels), 0.0), 'return' => array_fill(0, count($labels), 0.0)];

        // 2) Jika penjualan masih nol & tidak ada trx total, fallback ke invoices (hanya sales)
        if (!$trxCol) {
            $salesOnly = $this->seriesDailyFromInvoices($days, $wid);
            $base['sale'] = $salesOnly['sale'];
        }

        // 3) Fallback khusus transfer/return:
        //    bila 100% nol, hitung nilai dari transaction_details (line_total/price*qty - discount)
        if ($this->allZero($base['transfer']) || $this->allZero($base['return'])) {
            $fallback = $this->seriesDailyFromDetailsValue($days, $wid);
            if ($this->allZero($base['transfer'])) {
                $base['transfer'] = $fallback['transfer'];
            }
            if ($this->allZero($base['return'])) {
                $base['return'] = $fallback['return'];
            }
        }

        return [$labels, $base['sale'], $base['transfer'], $base['return']];
    }

    protected function seriesMonthly(int $months, ?int $wid): array
    {
        $end   = Carbon::now()->startOfMonth();
        $start = $end->copy()->subMonths($months - 1);

        $labels = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $labels[] = $cursor->isoFormat('MMM YYYY');
            $cursor->addMonth();
        }

        $trxCol = $this->resolveTransactionTotalColumn();
        $base   = $trxCol
            ? $this->seriesMonthlyFromTransactions($months, $wid, $trxCol)
            : ['labels' => $labels, 'sale' => array_fill(0, count($labels), 0.0), 'transfer' => array_fill(0, count($labels), 0.0), 'return' => array_fill(0, count($labels), 0.0)];

        if (!$trxCol) {
            $salesOnly = $this->seriesMonthlyFromInvoices($months, $wid);
            $base['sale'] = $salesOnly['sale'];
        }

        if ($this->allZero($base['transfer']) || $this->allZero($base['return'])) {
            $fallback = $this->seriesMonthlyFromDetailsValue($months, $wid);
            if ($this->allZero($base['transfer'])) {
                $base['transfer'] = $fallback['transfer'];
            }
            if ($this->allZero($base['return'])) {
                $base['return'] = $fallback['return'];
            }
        }

        return [$labels, $base['sale'], $base['transfer'], $base['return']];
    }

    /* =========================================================================
     |  DAILY helpers
     * ========================================================================= */

    protected function seriesDailyFromTransactions(int $days, ?int $wid, string $trxCol): array
    {
        $end    = Carbon::today();
        $start  = $end->copy()->subDays($days - 1);
        $period = CarbonPeriod::create($start, $end);

        $types = [$this->saleTypeVal, $this->transferTypeVal, $this->returnTypeVal];
        $tTbl  = (new Transaction)->getTable();

        $rows = DB::table($tTbl . ' as t')
            ->selectRaw("DATE(t.created_at) as d, t.{$this->trxTypeCol} as tt, SUM(t.`{$trxCol}`) as s")
            ->whereBetween('t.created_at', [$start->startOfDay(), $end->endOfDay()])
            ->whereIn("t.{$this->trxTypeCol}", $types)
            ->when($wid, function ($q) use ($wid) {
                $q->where(function ($qq) use ($wid) {
                    $qq->where('t.source_warehouse_id', $wid)
                       ->orWhere('t.destination_warehouse_id', $wid);
                });
            })
            ->groupBy('d', 'tt')
            ->get()
            ->groupBy('d');

        $labels = [];
        $sale = []; $transfer = []; $return = [];
        foreach ($period as $date) {
            $key  = $date->toDateString();
            $pack = $rows->get($key, collect());
            $labels[]  = $date->format('d M');
            $sale[]    = round((float) ($pack->firstWhere('tt', $this->saleTypeVal)->s ?? 0), 2);
            $transfer[]= round((float) ($pack->firstWhere('tt', $this->transferTypeVal)->s ?? 0), 2);
            $return[]  = round((float) ($pack->firstWhere('tt', $this->returnTypeVal)->s ?? 0), 2);
        }

        return ['labels' => $labels, 'sale' => $sale, 'transfer' => $transfer, 'return' => $return];
    }

    protected function seriesDailyFromInvoices(int $days, ?int $wid): array
    {
        $end    = Carbon::today();
        $start  = $end->copy()->subDays($days - 1);
        $period = CarbonPeriod::create($start, $end);

        $rows = Invoice::query()
            ->selectRaw('DATE(created_at) as d, SUM(' . $this->invoiceTotalCol . ') as s')
            ->when($wid, function ($q) use ($wid) {
                $q->whereHas('transaction', function ($t) use ($wid) {
                    $t->where('source_warehouse_id', $wid)
                      ->orWhere('destination_warehouse_id', $wid);
                });
            })
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy('d')
            ->pluck('s', 'd');

        $sale = []; $labels = [];
        foreach ($period as $date) {
            $labels[] = $date->format('d M');
            $sale[]   = round((float) ($rows[$date->toDateString()] ?? 0), 2);
        }
        return ['labels' => $labels, 'sale' => $sale];
    }

    protected function seriesDailyFromDetailsValue(int $days, ?int $wid): array
    {
        $end    = Carbon::today();
        $start  = $end->copy()->subDays($days - 1);
        $period = CarbonPeriod::create($start, $end);

        [$detailExpr, $needJoinCols] = $this->detailValueExpression();
        if (!$detailExpr) {
            // tak ada cara menghitung nilai dari details; kembalikan nol semua
            $n = iterator_count($period);
            return [
                'transfer' => array_fill(0, $n, 0.0),
                'return'   => array_fill(0, $n, 0.0),
            ];
        }

        $dTbl = (new TransactionDetail)->getTable();
        $tTbl = (new Transaction)->getTable();

        $types = [$this->transferTypeVal, $this->returnTypeVal];

        $rows = DB::table($dTbl . ' as d')
            ->join($tTbl . ' as t', "t.id", '=', "d.{$needJoinCols['trxFk']}")
            ->selectRaw("DATE(t.created_at) as d, t.{$this->trxTypeCol} as tt, SUM({$detailExpr}) as s")
            ->whereBetween('t.created_at', [$start->startOfDay(), $end->endOfDay()])
            ->whereIn("t.{$this->trxTypeCol}", $types)
            ->when($wid, function ($q) use ($wid) {
                $q->where(function ($qq) use ($wid) {
                    $qq->where('t.source_warehouse_id', $wid)
                       ->orWhere('t.destination_warehouse_id', $wid);
                });
            })
            ->groupBy('d', 'tt')
            ->get()
            ->groupBy('d');

        $transfer = []; $return = [];
        foreach ($period as $date) {
            $key = $date->toDateString();
            $pack = $rows->get($key, collect());
            $transfer[] = round((float) ($pack->firstWhere('tt', $this->transferTypeVal)->s ?? 0), 2);
            $return[]   = round((float) ($pack->firstWhere('tt', $this->returnTypeVal)->s ?? 0), 2);
        }

        return ['transfer' => $transfer, 'return' => $return];
    }

    /* =========================================================================
     |  MONTHLY helpers
     * ========================================================================= */

    protected function seriesMonthlyFromTransactions(int $months, ?int $wid, string $trxCol): array
    {
        $end   = Carbon::now()->startOfMonth();
        $start = $end->copy()->subMonths($months - 1);

        $tTbl  = (new Transaction)->getTable();
        $types = [$this->saleTypeVal, $this->transferTypeVal, $this->returnTypeVal];

        $rows = DB::table($tTbl . ' as t')
            ->selectRaw('DATE_FORMAT(t.created_at, "%Y-%m") as ym, t.' . $this->trxTypeCol . ' as tt, SUM(t.`' . $trxCol . '`) as s')
            ->whereBetween('t.created_at', [$start, $end->copy()->endOfMonth()])
            ->whereIn('t.' . $this->trxTypeCol, $types)
            ->when($wid, function ($q) use ($wid) {
                $q->where(function ($qq) use ($wid) {
                    $qq->where('t.source_warehouse_id', $wid)
                       ->orWhere('t.destination_warehouse_id', $wid);
                });
            })
            ->groupBy('ym', 'tt')
            ->get()
            ->groupBy('ym');

        $labels = []; $sale = []; $transfer = []; $return = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $ym   = $cursor->format('Y-m');
            $pack = $rows->get($ym, collect());
            $labels[]  = $cursor->isoFormat('MMM YYYY');
            $sale[]    = round((float) ($pack->firstWhere('tt', $this->saleTypeVal)->s ?? 0), 2);
            $transfer[]= round((float) ($pack->firstWhere('tt', $this->transferTypeVal)->s ?? 0), 2);
            $return[]  = round((float) ($pack->firstWhere('tt', $this->returnTypeVal)->s ?? 0), 2);
            $cursor->addMonth();
        }

        return ['labels' => $labels, 'sale' => $sale, 'transfer' => $transfer, 'return' => $return];
    }

    protected function seriesMonthlyFromInvoices(int $months, ?int $wid): array
    {
        $end   = Carbon::now()->startOfMonth();
        $start = $end->copy()->subMonths($months - 1);

        $rows = Invoice::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, SUM(' . $this->invoiceTotalCol . ') as s')
            ->when($wid, function ($q) use ($wid) {
                $q->whereHas('transaction', function ($t) use ($wid) {
                    $t->where('source_warehouse_id', $wid)
                      ->orWhere('destination_warehouse_id', $wid);
                });
            })
            ->whereBetween('created_at', [$start, $end->copy()->endOfMonth()])
            ->groupBy('ym')
            ->pluck('s', 'ym');

        $labels = []; $sale = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $ym = $cursor->format('Y-m');
            $labels[] = $cursor->isoFormat('MMM YYYY');
            $sale[]   = round((float) ($rows[$ym] ?? 0), 2);
            $cursor->addMonth();
        }

        return ['labels' => $labels, 'sale' => $sale];
    }

    protected function seriesMonthlyFromDetailsValue(int $months, ?int $wid): array
    {
        [$detailExpr, $needJoinCols] = $this->detailValueExpression();
        if (!$detailExpr) {
            return [
                'transfer' => array_fill(0, $months, 0.0),
                'return'   => array_fill(0, $months, 0.0),
            ];
        }

        $end   = Carbon::now()->startOfMonth();
        $start = $end->copy()->subMonths($months - 1);

        $dTbl = (new TransactionDetail)->getTable();
        $tTbl = (new Transaction)->getTable();

        $types = [$this->transferTypeVal, $this->returnTypeVal];

        $rows = DB::table($dTbl . ' as d')
            ->join($tTbl . ' as t', "t.id", '=', "d.{$needJoinCols['trxFk']}")
            ->selectRaw('DATE_FORMAT(t.created_at, "%Y-%m") as ym, t.' . $this->trxTypeCol . ' as tt, SUM(' . $detailExpr . ') as s')
            ->whereBetween('t.created_at', [$start, $end->copy()->endOfMonth()])
            ->whereIn('t.' . $this->trxTypeCol, $types)
            ->when($wid, function ($q) use ($wid) {
                $q->where(function ($qq) use ($wid) {
                    $qq->where('t.source_warehouse_id', $wid)
                       ->orWhere('t.destination_warehouse_id', $wid);
                });
            })
            ->groupBy('ym', 'tt')
            ->get()
            ->groupBy('ym');

        $labels = []; $transfer = []; $return = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $ym   = $cursor->format('Y-m');
            $pack = $rows->get($ym, collect());
            $labels[]  = $cursor->isoFormat('MMM YYYY');
            $transfer[]= round((float) ($pack->firstWhere('tt', $this->transferTypeVal)->s ?? 0), 2);
            $return[]  = round((float) ($pack->firstWhere('tt', $this->returnTypeVal)->s ?? 0), 2);
            $cursor->addMonth();
        }

        return ['transfer' => $transfer, 'return' => $return];
    }

    /* =========================================================================
     |  Resolver kolom & helper
     * ========================================================================= */

    /**
     * Ambil kolom total transaksi yang tersedia: grand_total -> total -> amount.
     * Return null jika tidak ada semua (akan fallback ke invoices/details).
     */
    protected function resolveTransactionTotalColumn(): ?string
    {
        $t = (new Transaction)->getTable();
        if (Schema::hasColumn($t, 'grand_total')) return 'grand_total';
        if (Schema::hasColumn($t, 'total'))       return 'total';
        if (Schema::hasColumn($t, 'amount'))      return 'amount';
        return null;
    }

    /**
     * Bangun ekspresi SQL untuk nilai detail:
     * - Prioritas: line_total
     * - Fallback: price*qty - discount_amount
     * - Kalau semua tidak ada → null
     */
    protected function detailValueExpression(): array
    {
        $d = (new TransactionDetail)->getTable();

        $hasLineTotal = Schema::hasColumn($d, 'line_total');
        if ($hasLineTotal) {
            return ["COALESCE(d.line_total,0)", ['trxFk' => $this->resolveDetailTrxFkColumn()]];
        }

        $hasPrice = Schema::hasColumn($d, 'price');
        $hasQty   = Schema::hasColumn($d, 'qty');
        $hasDisc  = Schema::hasColumn($d, 'discount_amount');

        if ($hasPrice && $hasQty) {
            $expr = "COALESCE(d.price,0) * COALESCE(d.qty,0)" . ($hasDisc ? " - COALESCE(d.discount_amount,0)" : "");
            return [$expr, ['trxFk' => $this->resolveDetailTrxFkColumn()]];
        }

        return [null, []];
    }

    protected function resolveDetailTrxFkColumn(): string
    {
        $d = (new TransactionDetail)->getTable();
        foreach (['transaction_id', 'trx_id', 'transactions_id'] as $col) {
            if (Schema::hasColumn($d, $col)) return $col;
        }
        // Default paling umum
        return 'transaction_id';
    }

    protected function ctx(): array
    {
        $u   = Auth::user();
        $wid = (int) ($u->warehouse_id ?? 0);
        $isSuper = $u && method_exists($u, 'hasRole') && $u->hasRole('Superadmin');
        return [$wid, $isSuper];
    }

    protected function allZero(array $series): bool
    {
        foreach ($series as $v) {
            if (abs((float) $v) > 0.00001) return false;
        }
        return true;
    }
}
