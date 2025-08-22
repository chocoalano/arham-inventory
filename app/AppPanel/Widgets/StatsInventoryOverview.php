<?php

namespace App\AppPanel\Widgets;

use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Invoice;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\TransactionDetail;
use App\Models\Inventory\WarehouseVariantStock;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StatsInventoryOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Inventory';

    // ======================= KONFIGURASI GLOBAL =======================
    protected array $stockColumnCandidates    = ['stock', 'quantity', 'qty', 'stok'];
    protected array $minStockColumnCandidates = ['min_stock', 'minstok', 'minimum_stock', 'safety_stock'];

    protected string $invoiceTotalCol     = 'total_amount';
    protected string $transactionTotalCol = 'grand_total';

    protected string $trxTypeCol   = 'type';
    protected string $saleTypeVal  = 'penjualan';
    protected string $buyTypeVal   = 'purchase';

    protected array $trxDetailQtyCandidates   = ['quantity', 'qty', 'jumlah', 'qty_in', 'qty_out', 'kuantitas'];
    protected array $trxDetailTrxFkCandidates = ['transaction_id', 'trx_id', 'transactions_id'];
    protected array $trxDetailVarFkCandidates = ['product_variant_id', 'variant_id', 'product_id'];

    protected int $lowStockThreshold = 5;

    protected function getStats(): array
    {
        [$user, $wid, $isSuperadmin] = $this->currentUserContext();

        $today      = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        // --- Resolve kolom stok & min stok pada product_variants ---
        [$stockCol, $minStockCol] = $this->resolveVariantStockColumns();

        // =================== TOTAL SKU ===================
        // Superadmin: semua varian. Non-superadmin: varian yang ada (stok on-hand > 0) di gudangnya
        $totalSku = $isSuperadmin
            ? ProductVariant::count()
            : ProductVariant::query()
                ->whereHas('stocks', function ($q) use ($wid) {
                    $q->where('warehouse_id', $wid)
                      ->whereRaw('(COALESCE(qty,0) - COALESCE(reserved_qty,0)) > 0');
                })
                ->count();

        // =================== TOTAL STOK / LOW / OOS ===================
        if ($isSuperadmin) {
            // Tetap ikuti logika global lama
            if ($stockCol) {
                $totalStock     = (int) ProductVariant::sum($stockCol);
                $lowStockCount  = $minStockCol
                    ? ProductVariant::whereColumn($stockCol, '<=', $minStockCol)->count()
                    : ProductVariant::where($stockCol, '<=', $this->lowStockThreshold)->count();
                $outOfStockCount = ProductVariant::where($stockCol, '<=', 0)->count();
            } else {
                $variantStocks   = $this->computeVariantStocksFromTransactions();
                $totalStock      = (int) $variantStocks->values()->sum();
                $outOfStockCount = $variantStocks->filter(fn ($v) => ($v ?? 0) <= 0)->count();
                if ($minStockCol) {
                    $minMap = ProductVariant::select('id', $minStockCol)->get()->pluck($minStockCol, 'id');
                    $lowStockCount = $variantStocks->filter(function ($stock, $id) use ($minMap) {
                        $min = (int) ($minMap[$id] ?? 0);
                        return $stock <= $min;
                    })->count();
                } else {
                    $lowStockCount = $variantStocks->filter(fn ($v) => ($v ?? 0) <= $this->lowStockThreshold)->count();
                }
            }
        } else {
            // Non-superadmin: hitung per-warehouse dari tabel stok per-gudang
            $stockTable = (new WarehouseVariantStock)->getTable();

            $totalStock = (int) DB::table($stockTable)
                ->where('warehouse_id', $wid)
                ->sum('qty');

            $outOfStockCount = (int) DB::table($stockTable)
                ->where('warehouse_id', $wid)
                ->where('qty', '<=', 0)
                ->count();

            if ($minStockCol) {
                // JOIN ke product_variants untuk bandingkan qty <= min_stock
                $pvTable = (new ProductVariant)->getTable();
                $lowStockCount = (int) DB::table("$pvTable as pv")
                    ->leftJoin("$stockTable as s", function ($j) use ($wid) {
                        $j->on('s.product_variant_id', '=', 'pv.id')
                          ->where('s.warehouse_id', '=', $wid);
                    })
                    ->whereColumn('s.qty', '<=', "pv.$minStockCol")
                    ->count('pv.id');
            } else {
                $lowStockCount = (int) DB::table($stockTable)
                    ->where('warehouse_id', $wid)
                    ->where('qty', '<=', $this->lowStockThreshold)
                    ->count();
            }
        }

        // =================== PENJUALAN HARI INI ===================
        $invToday = Invoice::query()
            ->when(!$isSuperadmin, function ($q) use ($wid) {
                $q->whereHas('transaction', function ($t) use ($wid) {
                    $t->where('source_warehouse_id', $wid)
                      ->orWhere('destination_warehouse_id', $wid);
                });
            })
            ->whereDate('created_at', $today);

        $todaySales = (float) $invToday->sum($this->invoiceTotalCol);

        // =================== OMZET BULAN INI ===================
        $trxMonthly = Transaction::query()
            ->where($this->trxTypeCol, $this->saleTypeVal)
            ->when(!$isSuperadmin, function ($q) use ($wid) {
                $q->where(function ($qq) use ($wid) {
                    $qq->where('source_warehouse_id', $wid)
                       ->orWhere('destination_warehouse_id', $wid);
                });
            })
            ->whereBetween('created_at', [$monthStart, $today->copy()->endOfDay()]);

        $monthlyRevenue = (float) $trxMonthly->sum($this->transactionTotalCol);

        if ($monthlyRevenue <= 0) {
            $invMonthly = Invoice::query()
                ->when(!$isSuperadmin, function ($q) use ($wid) {
                    $q->whereHas('transaction', function ($t) use ($wid) {
                        $t->where('source_warehouse_id', $wid)
                          ->orWhere('destination_warehouse_id', $wid);
                    });
                })
                ->whereBetween('created_at', [$monthStart, $today->copy()->endOfDay()]);

            $monthlyRevenue = (float) $invMonthly->sum($this->invoiceTotalCol);
        }

        // =================== SPARKLINE & TREN 7 HARI ===================
        [$sparkline, $lastWeekSum, $prevWeekSum] = $this->salesSparkline(7, $isSuperadmin ? null : $wid);
        [$trendDesc, $trendColor, $trendIcon]    = $this->trendMeta($lastWeekSum, $prevWeekSum);

        return [
            Stat::make('Total SKU', number_format($totalSku))
                ->description($isSuperadmin ? 'Semua varian' : 'Varian tersedia di gudang Anda')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info'),

            Stat::make('Total Stok', number_format($totalStock))
                ->description($isSuperadmin
                    ? ($stockCol ? "Dari kolom `{$stockCol}`" : 'Dihitung dari transaksi')
                    : 'Akumulasi stok di gudang Anda')
                ->descriptionIcon('heroicon-o-cube')
                ->color('success'),

            Stat::make('Stok Rendah', number_format($lowStockCount))
                ->description($minStockCol ? "Mengacu `{$minStockCol}`" : "Threshold ≤ {$this->lowStockThreshold}")
                ->descriptionIcon('heroicon-o-bell-alert')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),

            Stat::make('Di luar Stok', number_format($outOfStockCount))
                ->description($isSuperadmin ? 'Global' : 'Berdasar gudang Anda')
                ->descriptionIcon('heroicon-o-no-symbol')
                ->color($outOfStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Penjualan Hari Ini', $this->money($todaySales))
                ->description('Update per ' . $today->format('d M Y'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($todaySales > 0 ? 'success' : 'info'),

            Stat::make('Omzet Bulan Ini', $this->money($monthlyRevenue))
                ->description($trendDesc)
                ->descriptionIcon($trendIcon)
                ->color($trendColor)
                ->chart($sparkline),
        ];
    }

    /** ======================= Helper: user/warehouse context ======================= */
    protected function currentUserContext(): array
    {
        $user = Auth::user();
        $wid  = (int) ($user->warehouse_id ?? 0);
        $isSuperadmin = $user && method_exists($user, 'hasRole') && $user->hasRole('Superadmin');
        return [$user, $wid, $isSuperadmin];
    }

    /** ======================= Kolom stok/min stok di product_variants ======================= */
    protected function resolveVariantStockColumns(): array
    {
        $table = (new ProductVariant)->getTable();

        $stockCol = collect($this->stockColumnCandidates)
            ->first(fn ($c) => Schema::hasColumn($table, $c)) ?: null;

        $minCol = collect($this->minStockColumnCandidates)
            ->first(fn ($c) => Schema::hasColumn($table, $c)) ?: null;

        return [$stockCol, $minCol];
    }

    /** ======================= Resolve kolom detail trx ======================= */
    protected function resolveDetailColumns(): array
    {
        $detailTable = (new TransactionDetail)->getTable();
        $trxTable    = (new Transaction)->getTable();

        $qtyCol = collect($this->trxDetailQtyCandidates)->first(fn ($c) => Schema::hasColumn($detailTable, $c));
        $trxFk  = collect($this->trxDetailTrxFkCandidates)->first(fn ($c) => Schema::hasColumn($detailTable, $c));
        $varFk  = collect($this->trxDetailVarFkCandidates)->first(fn ($c) => Schema::hasColumn($detailTable, $c));

        if (!$qtyCol) {
            $cols = Schema::getColumnListing($detailTable);
            throw new RuntimeException("Tidak menemukan kolom qty di `{$detailTable}`. Kandidat: " . implode(', ', $this->trxDetailQtyCandidates) . '. Kolom: ' . implode(', ', $cols));
        }
        if (!$trxFk) {
            $cols = Schema::getColumnListing($detailTable);
            throw new RuntimeException("Tidak menemukan FK transaksi di `{$detailTable}`. Kandidat: " . implode(', ', $this->trxDetailTrxFkCandidates) . '. Kolom: ' . implode(', ', $cols));
        }
        if (!$varFk) {
            $cols = Schema::getColumnListing($detailTable);
            throw new RuntimeException("Tidak menemukan FK variant di `{$detailTable}`. Kandidat: " . implode(', ', $this->trxDetailVarFkCandidates) . '. Kolom: ' . implode(', ', $cols));
        }

        return [$detailTable, $trxTable, $qtyCol, $trxFk, $varFk];
    }

    /** ======================= Stok varian (global) dari transaksi ======================= */
    protected function computeVariantStocksFromTransactions(): Collection
    {
        [$detailTable, $trxTable, $qtyCol, $trxFk, $varFk] = $this->resolveDetailColumns();

        $rows = DB::table($detailTable . ' as d')
            ->join($trxTable . ' as t', "t.id", '=', "d.{$trxFk}")
            ->select([
                DB::raw("d.`{$varFk}` as variant_id"),
                DB::raw(
                    "SUM(CASE WHEN t.`{$this->trxTypeCol}` = " . DB::getPdo()->quote($this->buyTypeVal) . " THEN d.`{$qtyCol}` ELSE 0 END)" .
                    " - " .
                    "SUM(CASE WHEN t.`{$this->trxTypeCol}` = " . DB::getPdo()->quote($this->saleTypeVal) . " THEN d.`{$qtyCol}` ELSE 0 END) as stock"
                ),
            ])
            ->groupBy("d.{$varFk}")
            ->pluck('stock', 'variant_id');

        return $rows->map(fn ($v) => (int) $v);
    }

    /** ======================= Sparkline Invoices, support filter warehouse ======================= */
    protected function salesSparkline(int $days, ?int $wid = null): array
    {
        $end   = Carbon::today();
        $start = $end->copy()->subDays($days - 1);
        $period = CarbonPeriod::create($start, $end);

        $rowsQuery = Invoice::selectRaw('DATE(created_at) as d, SUM(' . $this->invoiceTotalCol . ') as s')
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->when($wid, function ($q) use ($wid) {
                $q->whereHas('transaction', function ($t) use ($wid) {
                    $t->where('source_warehouse_id', $wid)
                      ->orWhere('destination_warehouse_id', $wid);
                });
            })
            ->groupBy('d');

        $rows = $rowsQuery->pluck('s', 'd');

        $series = [];
        foreach ($period as $date) {
            $series[] = (float) ($rows[$date->toDateString()] ?? 0);
        }

        // Bandingkan dengan 7 hari sebelumnya (ikut filter warehouse)
        $prevStart = $start->copy()->subDays($days);
        $prevEnd   = $start->copy()->subDay();

        $prevSum = (float) Invoice::when($wid, function ($q) use ($wid) {
                $q->whereHas('transaction', function ($t) use ($wid) {
                    $t->where('source_warehouse_id', $wid)
                      ->orWhere('destination_warehouse_id', $wid);
                });
            })
            ->whereBetween('created_at', [$prevStart->startOfDay(), $prevEnd->endOfDay()])
            ->sum($this->invoiceTotalCol);

        $lastSum = array_sum($series);

        return [$series, $lastSum, $prevSum];
    }

    protected function trendMeta(float $last, float $prev): array
    {
        if ($prev <= 0 && $last > 0) {
            return ['Naik dibanding periode sebelumnya', 'success', 'heroicon-m-arrow-trending-up'];
        }
        if ($last >= $prev) {
            return [sprintf('Naik %.1f%%', $prev > 0 ? (($last - $prev) / $prev) * 100 : 0), 'success', 'heroicon-m-arrow-trending-up'];
        }
        return [sprintf('Turun %.1f%%', $prev > 0 ? (($prev - $last) / $prev) * 100 : 0), 'danger', 'heroicon-m-arrow-trending-down'];
    }

    protected function money(float $amount, string $currency = 'Rp'): string
    {
        return $currency . ' ' . number_format($amount, 0, ',', '.');
    }
}
