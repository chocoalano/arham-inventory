<?php

namespace App\AppPanel\Widgets;

use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Invoice;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\TransactionDetail;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StatsInventoryOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Inventory';

    /**
     * =======================
     *  KONFIGURASI GLOBAL
     * =======================
     */
    // Kandidat kolom stok di product_variants
    protected array $stockColumnCandidates = ['stock', 'quantity', 'qty', 'stok'];
    // Kandidat kolom min stock
    protected array $minStockColumnCandidates = ['min_stock', 'minstok', 'minimum_stock', 'safety_stock'];

    // Kolom total invoice
    protected string $invoiceTotalCol = 'total_amount';
    // Kolom total transaksi (jika ada)
    protected string $transactionTotalCol = 'grand_total';

    // Kolom tipe transaksi + nilai sale/purchase
    protected string $trxTypeCol = 'type';
    protected string $saleTypeVal = 'penjualan';
    protected string $buyTypeVal = 'purchase';

    // ===== DETAIL TRANSAKSI =====
    // Kandidat kolom QTY pada transaction_details
    protected array $trxDetailQtyCandidates = ['quantity', 'qty', 'jumlah', 'qty_in', 'qty_out', 'kuantitas'];

    // FK umum pada transaction_details
    protected array $trxDetailTrxFkCandidates = ['transaction_id', 'trx_id', 'transactions_id'];
    protected array $trxDetailVarFkCandidates = ['product_variant_id', 'variant_id', 'product_id'];

    // Fallback threshold low-stock jika tidak ada min_stock
    protected int $lowStockThreshold = 5;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        // --- Resolve kolom stok & min stok pada product_variants ---
        [$stockCol, $minStockCol] = $this->resolveVariantStockColumns();

        // --- Totals SKU ---
        $totalSku = ProductVariant::count();

        // --- Stok: langsung dari kolom atau dihitung dari transaksi ---
        if ($stockCol) {
            $totalStock = (int) ProductVariant::sum($stockCol);
            $lowStockCount = $minStockCol
                ? ProductVariant::whereColumn($stockCol, '<=', $minStockCol)->count()
                : ProductVariant::where($stockCol, '<=', $this->lowStockThreshold)->count();
            $outOfStockCount = ProductVariant::where($stockCol, '<=', 0)->count();
        } else {
            // Mode transaksi
            $variantStocks = $this->computeVariantStocksFromTransactions(); // auto-detect qty/trx_id/variant_id
            $totalStock = (int) $variantStocks->values()->sum();
            $outOfStockCount = $variantStocks->filter(fn($v) => ($v ?? 0) <= 0)->count();

            if ($minStockCol) {
                $minMap = ProductVariant::select('id', $minStockCol)->get()->pluck($minStockCol, 'id');
                $lowStockCount = $variantStocks->filter(function ($stock, $id) use ($minMap) {
                    $min = (int) ($minMap[$id] ?? 0);
                    return $stock <= $min;
                })->count();
            } else {
                $lowStockCount = $variantStocks->filter(fn($v) => ($v ?? 0) <= $this->lowStockThreshold)->count();
            }
        }

        // --- Penjualan hari ini (Invoices) ---
        $todaySales = (float) Invoice::whereDate('created_at', $today)->sum($this->invoiceTotalCol);

        // --- Omzet bulan ini (Transactions sale → fallback Invoices) ---
        $monthlyRevenue = (float) Transaction::where($this->trxTypeCol, $this->saleTypeVal)
            ->whereBetween('created_at', [$monthStart, $today->copy()->endOfDay()])
            ->sum($this->transactionTotalCol);

        if ($monthlyRevenue <= 0) {
            $monthlyRevenue = (float) Invoice::whereBetween('created_at', [$monthStart, $today->copy()->endOfDay()])
                ->sum($this->invoiceTotalCol);
        }

        // --- Sparkline & tren 7 hari (Invoices) ---
        [$sparkline, $lastWeekSum, $prevWeekSum] = $this->salesSparkline(7);
        [$trendDesc, $trendColor, $trendIcon] = $this->trendMeta($lastWeekSum, $prevWeekSum);

        return [
            Stat::make('Total SKU', number_format($totalSku))
                ->description('Jumlah varian aktif')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info'),

            Stat::make('Total Stok', number_format($totalStock))
                ->description($stockCol ? "Dari kolom `{$stockCol}`" : 'Dihitung dari transaksi')
                ->descriptionIcon('heroicon-o-cube')
                ->color('success'),

            Stat::make('Low Stock', number_format($lowStockCount))
                ->description($minStockCol ? "Mengacu `{$minStockCol}`" : "Threshold ≤ {$this->lowStockThreshold}")
                ->descriptionIcon('heroicon-o-bell-alert')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),

            Stat::make('Out of Stock', number_format($outOfStockCount))
                ->description('Habis / tidak tersedia')
                ->descriptionIcon('heroicon-o-no-symbol')
                ->color($outOfStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Penjualan Hari Ini', $this->money($todaySales))
                ->description('Update per ' . $today->format('d M Y'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($todaySales > 0 ? 'success' : 'info'),

            Stat::make('Omzet Bulan Ini', $this->money($monthlyRevenue))
                ->description($trendDesc)
                ->descriptionIcon($trendIcon) // sudah string 'heroicon-m-arrow-trending-up/down'
                ->color($trendColor)
                ->chart($sparkline),
        ];
    }

    /**
     * Pilih kolom stok & min stok yang tersedia pada tabel product_variants.
     */
    protected function resolveVariantStockColumns(): array
    {
        $table = (new ProductVariant)->getTable();

        $stockCol = collect($this->stockColumnCandidates)
            ->first(fn($c) => Schema::hasColumn($table, $c)) ?: null;

        $minCol = collect($this->minStockColumnCandidates)
            ->first(fn($c) => Schema::hasColumn($table, $c)) ?: null;

        return [$stockCol, $minCol];
    }

    /**
     * Resolve kolom qty & foreign keys pada transaction_details.
     */
    protected function resolveDetailColumns(): array
    {
        $detailTable = (new TransactionDetail)->getTable();
        $trxTable = (new Transaction)->getTable();

        $qtyCol = collect($this->trxDetailQtyCandidates)
            ->first(fn($c) => Schema::hasColumn($detailTable, $c));

        $trxFk = collect($this->trxDetailTrxFkCandidates)
            ->first(fn($c) => Schema::hasColumn($detailTable, $c));

        $varFk = collect($this->trxDetailVarFkCandidates)
            ->first(fn($c) => Schema::hasColumn($detailTable, $c));

        if (!$qtyCol) {
            $cols = Schema::getColumnListing($detailTable);
            throw new RuntimeException(
                "Tidak menemukan kolom qty di `{$detailTable}`. Kandidat dicek: " .
                implode(', ', $this->trxDetailQtyCandidates) .
                '. Kolom yang ada: ' . implode(', ', $cols)
            );
        }
        if (!$trxFk) {
            $cols = Schema::getColumnListing($detailTable);
            throw new RuntimeException(
                "Tidak menemukan FK transaksi di `{$detailTable}`. Kandidat: " .
                implode(', ', $this->trxDetailTrxFkCandidates) .
                '. Kolom yang ada: ' . implode(', ', $cols)
            );
        }
        if (!$varFk) {
            $cols = Schema::getColumnListing($detailTable);
            throw new RuntimeException(
                "Tidak menemukan FK variant di `{$detailTable}`. Kandidat: " .
                implode(', ', $this->trxDetailVarFkCandidates) .
                '. Kolom yang ada: ' . implode(', ', $cols)
            );
        }

        return [$detailTable, $trxTable, $qtyCol, $trxFk, $varFk];
    }

    /**
     * Hitung stok per variant dari transaksi:
     * stock = SUM(qty purchase) - SUM(qty sale)
     * return: Collection<variant_id => stock_int>
     */
    protected function computeVariantStocksFromTransactions(): Collection
    {
        [$detailTable, $trxTable, $qtyCol, $trxFk, $varFk] = $this->resolveDetailColumns();

        // SELECT d.{varFk} AS variant_id,
        //   SUM(CASE WHEN t.type='purchase' THEN d.{qtyCol} ELSE 0 END)
        // - SUM(CASE WHEN t.type='sale'     THEN d.{qtyCol} ELSE 0 END) AS stock
        // FROM {detailTable} d
        // JOIN {trxTable} t ON t.id = d.{trxFk}
        // GROUP BY d.{varFk}

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

        return $rows->map(fn($v) => (int) $v);
    }

    /**
     * Sparkline 7 hari terakhir dari Invoices.
     * return: [series<float>, lastWeekSum<float>, prevWeekSum<float>]
     */
    protected function salesSparkline(int $days): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);
        $period = CarbonPeriod::create($start, $end);

        $rows = Invoice::selectRaw('DATE(created_at) as d, SUM(' . $this->invoiceTotalCol . ') as s')
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy('d')
            ->pluck('s', 'd');

        $series = [];
        foreach ($period as $date) {
            $series[] = (float) ($rows[$date->toDateString()] ?? 0);
        }

        // Bandingkan 7 hari sebelumnya
        $prevStart = $start->copy()->subDays($days);
        $prevEnd = $start->copy()->subDay();

        $prevSum = (float) Invoice::whereBetween('created_at', [$prevStart->startOfDay(), $prevEnd->endOfDay()])
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
