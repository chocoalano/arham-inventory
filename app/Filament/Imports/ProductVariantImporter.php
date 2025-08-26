<?php

namespace App\Filament\Imports;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Warehouse;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

class ProductVariantImporter extends Importer
{
    protected static ?string $model = ProductVariant::class;

    /**
     * Definisi kolom yang bisa di-mapping dari file CSV/XLSX.
     * Kamu bisa memetakan header yang berbeda saat proses import di UI Filament.
     */
    public static function getColumns(): array
    {
        return [
            // Identitas produk induk (pilih salah satu yang kamu miliki di file)
            ImportColumn::make('product_sku')
                ->label('SKU Produk (induk)')
                ->rules(['nullable', 'string', 'max:64'])
                ->example('NIKE-AMAX-250825-ABCD'),

            ImportColumn::make('product_id')
                ->label('ID Produk (induk)')
                ->rules(['nullable', 'integer', 'min:1'])
                ->example('1'),

            // Varian
            ImportColumn::make('sku_variant')
                ->label('SKU Varian')
                ->rules(['nullable', 'string', 'max:64'])
                ->example('NIKE-AMAX-RED-42-250825-ABCD'),

            ImportColumn::make('barcode')
                ->label('Barcode')
                ->rules(['nullable', 'string', 'max:64'])
                ->example('8999991234567'),

            ImportColumn::make('color')
                ->label('Warna')
                ->rules(['nullable', 'string', 'max:50'])
                ->example('Merah'),

            ImportColumn::make('size')
                ->label('Ukuran')
                ->requiredMapping() // wajib dipetakan di UI
                ->rules(['required', 'string', 'max:50'])
                ->example('M'),

            ImportColumn::make('cost_price')
                ->label('Harga Modal')
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('125000'),

            ImportColumn::make('price')
                ->label('Harga Jual')
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('175000'),

            ImportColumn::make('status')
                ->label('Status')
                ->rules(['nullable', Rule::in(['active', 'inactive', 'discontinued'])])
                ->example('active'),

            // (Opsional) stok awal per gudang
            ImportColumn::make('warehouse_code')
                ->label('Kode Gudang (untuk stok awal)')
                ->rules(['nullable', 'string', 'max:32'])
                ->example('WH-A'),

            ImportColumn::make('init_qty')
                ->label('Qty Awal (opsional)')
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('10'),

            ImportColumn::make('init_reserved_qty')
                ->label('Reserved Qty Awal (opsional)')
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('0'),
        ];
    }

    /**
     * Tentukan record yang akan dibuat/diupdate.
     * Prioritas pencarian:
     *  1) (product_id|product_sku) + color + size (karena ada unique composite)
     *  2) sku_variant (fallback)
     * Jika tidak ketemu → buat baru.
     */
    public function resolveRecord(): ProductVariant
    {
        $productId = $this->resolveProductIdFromRow($this->data);
        $color     = $this->normalizeColor($this->data['color'] ?? null);
        $size      = $this->normalizeSize($this->data['size'] ?? null);
        $skuVar    = $this->data['sku_variant'] ?? null;

        // Coba berdasarkan composite: product_id + color + size (abaikan yang soft-deleted)
        if ($productId && $color && $size) {
            $existing = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('color', $color)
                ->where('size',  $size)
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // Fallback: berdasarkan sku_variant
        if ($skuVar) {
            $existingBySku = ProductVariant::query()
                ->where('sku_variant', $skuVar)
                ->first();

            if ($existingBySku) {
                return $existingBySku;
            }
        }

        // Tidak ada → new
        return new ProductVariant([
            'product_id' => $productId,
        ]);
    }

    /**
     * Pengisian field record (create/update).
     */
    public static function getRecordFillers(): array
    {
        return [
            // Product (resolve via product_sku atau product_id)
            ImportColumn::make('product_sku')
                ->fillRecordUsing(function (ProductVariant $record, $state, array $row) {
                    $productId = self::resolveProductIdStatic($row);
                    if ($productId) {
                        $record->product_id = $productId;
                    }
                }),

            ImportColumn::make('product_id')
                ->fillRecordUsing(function (ProductVariant $record, $state, array $row) {
                    $productId = self::resolveProductIdStatic($row);
                    if ($productId) {
                        $record->product_id = $productId;
                    }
                }),

            // Warna
            ImportColumn::make('color')
                ->fillRecordUsing(function (ProductVariant $record, $state) {
                    $record->color = self::normalizeColorStatic($state);
                }),

            // Ukuran (validasi in-array + unique composite)
            ImportColumn::make('size')
                ->rules([Rule::in(ProductVariant::SIZES)])
                ->validationMessages([
                    'in' => 'Ukuran tidak valid. Pilih salah satu: ' . implode(', ', ProductVariant::SIZES),
                ])
                ->fillRecordUsing(function (ProductVariant $record, $state) {
                    $record->size = self::normalizeSizeStatic($state);
                }),

            // SKU Varian (auto-generate jika kosong)
            ImportColumn::make('sku_variant')
                ->fillRecordUsing(function (ProductVariant $record, $state, array $row) {
                    $skuVar = filled($state) ? trim((string) $state) : null;

                    if (blank($skuVar)) {
                        // generate berbasis SKU product + color + size
                        $productSku = self::resolveProductSkuStatic($row);
                        $skuVar = ProductVariant::generateUniqueSkuVariant(
                            productSku: $productSku,
                            color: self::normalizeColorStatic($row['color'] ?? null),
                            size:  self::normalizeSizeStatic($row['size'] ?? null),
                        );
                    }

                    // pastikan unik saat create
                    if (! $record->exists) {
                        while (ProductVariant::query()->where('sku_variant', $skuVar)->exists()) {
                            $skuVar = ProductVariant::generateUniqueSkuVariant($skuVar);
                        }
                    }

                    $record->sku_variant = $skuVar;
                }),

            // Barcode (unik nullable)
            ImportColumn::make('barcode')
                ->rules([
                    'nullable',
                    'string',
                    'max:64',
                    // Unik saat create / ignore saat update
                    Rule::unique('product_variants', 'barcode')->whereNull('deleted_at'),
                ])
                ->fillRecordUsing(function (ProductVariant $record, $state) {
                    $record->barcode = filled($state) ? trim((string) $state) : null;
                }),

            // Harga
            ImportColumn::make('cost_price')
                ->fillRecordUsing(function (ProductVariant $record, $state) {
                    if ($state !== null && $state !== '') {
                        $record->cost_price = (float) $state ?? null;
                    }
                }),

            ImportColumn::make('price')
                ->fillRecordUsing(function (ProductVariant $record, $state) {
                    if ($state !== null && $state !== '') {
                        $record->price = (float) $state ?? null;
                    }
                }),

            // Status
            ImportColumn::make('status')
                ->rules([Rule::in(['active', 'inactive', 'discontinued'])])
                ->validationMessages([
                    'in' => 'Status tidak valid. Pilih: active, inactive, atau discontinued.',
                ])
                ->fillRecordUsing(function (ProductVariant $record, $state) {
                    $val = strtolower((string) $state);
                    $record->status = in_array($val, ['active', 'inactive', 'discontinued'], true)
                        ? $val
                        : ($record->status ?? 'active');
                }),

            // Stok awal per gudang (opsional)
            ImportColumn::make('warehouse_code')
                ->fillRecordUsing(function (ProductVariant $record, $state, array $row) {
                    // Ditangani di afterSaveRow agar product_variant_id sudah ada
                }),

            ImportColumn::make('init_qty')
                ->fillRecordUsing(function (ProductVariant $record, $state, array $row) {
                    // Ditangani di afterSaveRow
                }),

            ImportColumn::make('init_reserved_qty')
                ->fillRecordUsing(function (ProductVariant $record, $state, array $row) {
                    // Ditangani di afterSaveRow
                }),
        ];
    }

    /**
     * Setelah tiap baris tersimpan, kita boleh isi stok awal per gudang (opsional).
     */
    public function afterSaveRow(): void
    {
        /** @var ProductVariant $variant */
        $variant = $this->record;

        $whCode = $this->data['warehouse_code'] ?? null;
        $qty    = $this->toInt($this->data['init_qty'] ?? null);
        $rq     = $this->toInt($this->data['init_reserved_qty'] ?? null);

        if (! $whCode || ($qty === null && $rq === null)) {
            return; // tidak ada pengisian stok
        }

        $warehouse = Warehouse::query()->where('code', trim((string) $whCode))->first();
        if (! $warehouse) {
            return;
        }

        // upsert stok
        $variant->stocks()->updateOrCreate(
            ['warehouse_id' => $warehouse->id],
            [
                'qty'          => $qty ?? 0,
                'reserved_qty' => $rq  ?? 0,
            ]
        );
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product variant import has completed and ' . Number::format($import->successful_rows)
            . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    /* =========================
     * Helpers
     * ========================= */

    protected function resolveProductIdFromRow(array $row): ?int
    {
        return self::resolveProductIdStatic($row);
    }

    protected static function resolveProductIdStatic(array $row): ?int
    {
        // 1) by product_id
        if (! empty($row['product_id'])) {
            return (int) $row['product_id'];
        }

        // 2) by product_sku
        if (! empty($row['product_sku'])) {
            $sku = strtoupper(trim((string) $row['product_sku']));
            return Product::query()->where('sku', $sku)->value('id');
        }

        return null;
    }

    protected static function resolveProductSkuStatic(array $row): ?string
    {
        if (! empty($row['product_sku'])) {
            return strtoupper(trim((string) $row['product_sku']));
        }

        // Jika tidak ada product_sku tapi ada product_id, ambil sku dari DB
        if (! empty($row['product_id'])) {
            $sku = Product::query()->where('id', (int) $row['product_id'])->value('sku');
            return $sku ? (string) $sku : null;
        }

        return null;
    }

    protected function normalizeColor(?string $val): ?string
    {
        return self::normalizeColorStatic($val);
    }

    protected static function normalizeColorStatic(?string $val): ?string
    {
        $val = trim((string) $val);
        return $val === '' ? null : $val; // biarkan case sesuai input, atau paksa lower: mb_strtolower($val)
    }

    protected function normalizeSize(?string $val): ?string
    {
        return self::normalizeSizeStatic($val);
    }

    protected static function normalizeSizeStatic(?string $val): ?string
    {
        $val = strtoupper(trim((string) $val));
        return $val === '' ? null : $val;
    }

    protected function toInt($v): ?int
    {
        if ($v === null || $v === '') return null;
        if (! is_numeric($v)) return null;
        return (int) $v;
    }
}
