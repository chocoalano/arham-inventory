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
     */
    public static function getColumns(): array
    {
        return [
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
                ->requiredMapping()
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

            // Stok awal per gudang (opsional)
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
     * Rules validasi tingkat-baris agar pesan error spesifik (bukan error sistem).
     */
    public function getValidationRules(): array
    {
        return [
            // Minimal salah satu identitas produk wajib ada & valid
            'product_id'  => ['nullable', 'integer', 'min:1', 'required_without:product_sku', 'bail', Rule::exists('products', 'id')],
            'product_sku' => ['nullable', 'string', 'max:64', 'required_without:product_id', 'bail', Rule::exists('products', 'sku')],

            // Varian
            'size'        => ['required', 'string', 'max:50'],
            'color'       => ['nullable', 'string', 'max:50'],

            // Unik & format
            'sku_variant' => ['nullable', 'string', 'max:64'],
            'barcode'     => ['nullable', 'string', 'max:64', Rule::unique('product_variants', 'barcode')->whereNull('deleted_at')],

            // Harga
            'cost_price'  => ['nullable', 'numeric', 'min:0'],
            'price'       => ['nullable', 'numeric', 'min:0'],

            // Status
            'status'      => ['nullable', Rule::in(['active', 'inactive', 'discontinued'])],

            // Stok awal (opsional)
            'warehouse_code'     => ['nullable', 'string', 'max:32'],
            'init_qty'           => ['nullable', 'integer', 'min:0'],
            'init_reserved_qty'  => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Pesan validation yang jelas.
     */
    public function getValidationMessages(): array
    {
        return [
            'product_id.required_without'  => 'Isi "ID Produk (induk)" atau "SKU Produk (induk)".',
            'product_sku.required_without' => 'Isi "SKU Produk (induk)" atau "ID Produk (induk)".',
            'product_id.exists'            => 'ID Produk (induk) tidak ditemukan di database.',
            'product_sku.exists'           => 'SKU Produk (induk) tidak ditemukan di database.',

            'size.required' => 'Ukuran wajib diisi.',
            'size.max'      => 'Ukuran maksimal :max karakter.',
            'color.max'     => 'Warna maksimal :max karakter.',

            'barcode.unique' => 'Barcode sudah digunakan oleh varian lain.',
            'status.in'      => 'Status tidak valid. Pilih: active, inactive, atau discontinued.',

            'cost_price.numeric' => 'Harga Modal harus berupa angka.',
            'price.numeric'      => 'Harga Jual harus berupa angka.',

            'init_qty.integer'          => 'Qty Awal harus bilangan bulat.',
            'init_reserved_qty.integer' => 'Reserved Qty Awal harus bilangan bulat.',
        ];
    }

    /**
     * Label atribut untuk pesan error yang ramah.
     */
    public function getValidationAttributes(): array
    {
        return [
            'product_id'        => 'ID Produk (induk)',
            'product_sku'       => 'SKU Produk (induk)',
            'sku_variant'       => 'SKU Varian',
            'barcode'           => 'Barcode',
            'color'             => 'Warna',
            'size'              => 'Ukuran',
            'cost_price'        => 'Harga Modal',
            'price'             => 'Harga Jual',
            'status'            => 'Status',
            'warehouse_code'    => 'Kode Gudang',
            'init_qty'          => 'Qty Awal',
            'init_reserved_qty' => 'Reserved Qty Awal',
        ];
    }

    /**
     * Tentukan record yang akan dibuat/diupdate.
     * Prioritas: (product + color + size) → sku_variant → new.
     * Jika tidak bisa menentukan product, tandai gagal dengan alasan.
     */
    public function resolveRecord(): ProductVariant
    {
        $productId = $this->resolveProductIdFromRow($this->data);
        $color     = $this->normalizeColor($this->data['color'] ?? null);
        $size      = $this->normalizeSize($this->data['size'] ?? null);
        $skuVar    = $this->data['sku_variant'] ?? null;

        if (! $productId) {
            $this->fail('Produk induk tidak ditemukan. Periksa "ID Produk (induk)" atau "SKU Produk (induk)".');
            return new ProductVariant(); // baris akan ditandai gagal
        }

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

        if ($skuVar) {
            $existingBySku = ProductVariant::query()
                ->where('sku_variant', $skuVar)
                ->first();

            if ($existingBySku) {
                return $existingBySku;
            }
        }

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

            ImportColumn::make('color')
                ->fillRecordUsing(function (ProductVariant $record, $state) {
                    $record->color = self::normalizeColorStatic($state);
                }),

            ImportColumn::make('size')
                ->rules([Rule::in(ProductVariant::SIZES)])
                ->validationMessages([
                    'in' => 'Ukuran tidak valid. Pilih salah satu: ' . implode(', ', ProductVariant::SIZES),
                ])
                ->fillRecordUsing(function (ProductVariant $record, $state) {
                    $record->size = self::normalizeSizeStatic($state);
                }),

            ImportColumn::make('sku_variant')
                ->fillRecordUsing(function (ProductVariant $record, $state, array $row) {
                    $skuVar = filled($state) ? trim((string) $state) : null;

                    if (blank($skuVar)) {
                        $productSku = self::resolveProductSkuStatic($row);
                        $skuVar = ProductVariant::generateUniqueSkuVariant(
                            productSku: $productSku,
                            color: self::normalizeColorStatic($row['color'] ?? null),
                            size:  self::normalizeSizeStatic($row['size'] ?? null),
                        );
                    }

                    if (! $record->exists) {
                        while (ProductVariant::query()->where('sku_variant', $skuVar)->exists()) {
                            $skuVar = ProductVariant::generateUniqueSkuVariant($skuVar);
                        }
                    }

                    $record->sku_variant = $skuVar;
                }),

            ImportColumn::make('barcode')
                ->rules([
                    'nullable',
                    'string',
                    'max:64',
                    Rule::unique('product_variants', 'barcode')->whereNull('deleted_at'),
                ])
                ->fillRecordUsing(function (ProductVariant $record, $state) {
                    $record->barcode = filled($state) ? trim((string) $state) : null;
                }),

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

            // Stok awal diselesaikan di afterSaveRow()
            ImportColumn::make('warehouse_code')
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('init_qty')
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('init_reserved_qty')
                ->fillRecordUsing(fn () => null),
        ];
    }

    /**
     * Setelah tiap baris tersimpan: isi stok awal (opsional) dgn validasi jelas.
     * Gagal → tandai baris gagal dengan $this->fail('alasan spesifik'), bukan error sistem.
     */
    public function afterSaveRow(): void
    {
        /** @var ProductVariant $variant */
        $variant = $this->record;

        $whCode = $this->data['warehouse_code'] ?? null;
        $qty    = $this->toInt($this->data['init_qty'] ?? null);
        $rq     = $this->toInt($this->data['init_reserved_qty'] ?? null);

        // Tidak ada input stok → selesai
        if (! $whCode && $qty === null && $rq === null) {
            return;
        }

        // Qty/Reserved ada tapi kode gudang kosong → gagal dengan pesan spesifik
        if (($qty !== null || $rq !== null) && blank($whCode)) {
            $this->fail('Gagal mengisi stok awal: "Kode Gudang" kosong sementara Qty/Reserved diisi.');
            return;
        }

        $warehouse = Warehouse::query()
            ->where('code', trim((string) $whCode))
            ->first();

        if (! $warehouse) {
            $this->fail("Gagal mengisi stok awal: Kode Gudang '{$whCode}' tidak ditemukan.");
            return;
        }

        // Upsert stok (aman)
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
        $body = 'Import varian produk selesai. '
            . Number::format($import->successful_rows) . ' '
            . str('baris')->plural($import->successful_rows) . ' berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                . str('baris')->plural($failedRowsCount) . ' gagal diimpor.';
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
        if (! empty($row['product_id'])) {
            return (int) $row['product_id'];
        }

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
        return $val === '' ? null : $val;
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
