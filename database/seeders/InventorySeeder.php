<?php

namespace Database\Seeders;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Supplier;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // Hemat RAM saat seeding
        DB::disableQueryLog();
        Model::unsetEventDispatcher();

        // ===== Knobs (atur dari .env) =====
        $WAREHOUSE_COUNT      = (int) env('SEED_WAREHOUSES', 3);
        $SUPPLIER_COUNT       = (int) env('SEED_SUPPLIERS', 5);
        $PRODUCT_COUNT        = (int) env('SEED_PRODUCTS', 10);
        $VARIANT_PER_PRODUCT  = (int) env('SEED_VARIANTS_PER_PRODUCT', 3);
        $IMAGES_PER_PRODUCT   = (int) env('SEED_IMAGES_PER_PRODUCT', 2);
        $STOCK_PER_WAREHOUSE  = (int) env('SEED_STOCK_PER_WAREHOUSE', 60); // stok awal per gudang (maks)
        $TX_SALES             = (int) env('SEED_TX_SALES', 10);
        $TX_TRANSFER          = (int) env('SEED_TX_TRANSFER', 6);
        $TX_RETURN            = (int) env('SEED_TX_RETURN', 4);

        // ===== Master =====
        Warehouse::factory($WAREHOUSE_COUNT)->create();
        Supplier::factory($SUPPLIER_COUNT)->create();

        // Produk + turunannya (terkendali)
        Product::factory($PRODUCT_COUNT)
            ->withVariants($VARIANT_PER_PRODUCT)
            ->withImages($IMAGES_PER_PRODUCT)
            ->create();

        // ===== Stok awal: batasi per gudang (hindari kartesius) =====
        $warehouseIds = Warehouse::pluck('id');
        $variantIds   = ProductVariant::pluck('id');

        foreach ($warehouseIds as $whId) {
            $take = min($STOCK_PER_WAREHOUSE, $variantIds->count());
            if ($take === 0) continue;

            // ambil subset acak varian untuk gudang ini
            $selected = $variantIds->random($take);

            // siapkan bulk insert agar hemat
            $rows = [];
            foreach ($selected as $varId) {
                $rows[] = [
                    'warehouse_id'       => $whId,
                    'product_variant_id' => $varId,
                    'qty'                => random_int(20, 200),
                    'reserved_qty'       => random_int(0, 20),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }
            // gunakan insert() + ignore duplikat (kalau pakai MySQL 8, bisa pakai upsert)
            DB::table('warehouse_variant_stocks')->insert($rows);
            unset($rows);
        }

        // ===== Transaksi (pakai katalog existing — tidak melahirkan produk/varian baru) =====
        // Dengan withAutoDetails() yang diperbaiki (lihat file factory di bawah).
        Transaction::factory($TX_SALES)->sale()->withAutoDetails()->create();
        Transaction::factory($TX_TRANSFER)->transfer()->withAutoDetails()->create();
        Transaction::factory($TX_RETURN)->returnIn()->withAutoDetails()->create();
    }
}
