<?php

namespace App\Filament\Imports;

use App\Models\Inventory\Product;
use App\Models\Inventory\Supplier;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    /**
     * Definisikan kolom yang boleh diimpor dari file.
     * Gunakan nama header sesuai contoh atau mapping manual saat import.
     */
    public static function getColumns(): array
    {
        return [
            // --- Identitas Produk ---
            ImportColumn::make('sku')
                ->label('SKU')
                ->rules([
                    'nullable',
                    'string',
                    'max:64',
                    // validasi unik hanya berlaku jika create record baru; di resolveRecord kita handle by SKU
                ])
                ->example('NIKE-AMAX-250825-ABCD')
                ->requiredMapping(),

            ImportColumn::make('name')
                ->label('Nama')
                ->requiredMapping() // user wajib memetakan kolom name
                ->rules(['required', 'string', 'max:200'])
                ->example('Nike Air Max 90'),

            ImportColumn::make('brand')
                ->label('Brand')
                ->rules(['nullable', 'string', 'max:100'])
                ->example('Nike'),

            ImportColumn::make('model')
                ->label('Model')
                ->rules(['nullable', 'string', 'max:100'])
                ->example('Air Max 90'),

            ImportColumn::make('description')
                ->label('Deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Sepatu lari ikonik dengan bantalan nyaman.'),

            // --- Relasi Supplier ---
            ImportColumn::make('supplier_code')
                ->label('Kode Supplier')
                ->relationship('supplier', 'code')
                ->rules(['nullable', 'string', 'max:32'])
                ->example('SUP-001'),

            ImportColumn::make('supplier_name')
                ->label('Nama Supplier')
                ->relationship('supplier', 'name')
                ->rules(['nullable', 'string', 'max:150'])
                ->example('PT Sumber Olahraga'),

            // --- Status ---
            ImportColumn::make('is_active')
                ->label('Aktif')
                ->rules(['nullable'])
                ->example('true'), // nilai yang diterima: true/false/1/0/yes/no/aktif/nonaktif
        ];
    }

    /**
     * Resolve record berdasarkan SKU: jika ada → update, kalau tidak ada → new.
     * Jika file tidak menyediakan SKU, kita generate di bawah (fillRecordUsing).
     */
    public function resolveRecord(): Product
    {
        $incomingSku = $this->data['sku'] ?? null;

        if ($incomingSku) {
            $sku = strtoupper(trim((string) $incomingSku));
            $found = Product::query()->where('sku', $sku)->first();
            if ($found) {
                return $found;
            }
        }

        // Create baru (SKU bisa dikosongkan dulu, akan diisi saat fillRecordUsing)
        return new Product();
    }

    /**
     * Isi setiap field ke record. Dipanggil untuk create maupun update.
     * Di sini kita juga handle:
     * - generate SKU jika kosong,
     * - cari/buat supplier,
     * - casting boolean untuk is_active.
     */
    public static function getRecordFillers(): array
    {
        return [
            // SKU
            ImportColumn::make('sku')
                ->fillRecordUsing(function (Product $record, $state, array $row) {
                    $sku = $state ? strtoupper(trim((string) $state)) : null;

                    if (blank($sku)) {
                        // generate dari brand/model/name
                        $seed = trim(implode(' ', array_filter([
                            $row['brand'] ?? null,
                            $row['model'] ?? null,
                            $row['name']  ?? null,
                        ])));
                        $sku = Product::generateUniqueSku($seed);
                    }

                    // pastikan unik jika creating
                    if (! $record->exists) {
                        while (Product::query()->where('sku', $sku)->exists()) {
                            // tabrakan (highly unlikely, tapi aman)
                            $sku = Product::generateUniqueSku($sku);
                        }
                    }

                    $record->sku = $sku;
                }),

            // Name
            ImportColumn::make('name')
                ->fillRecordUsing(function (Product $record, $state) {
                    $record->name = trim((string) $state);
                }),

            // Brand
            ImportColumn::make('brand')
                ->fillRecordUsing(function (Product $record, $state) {
                    $record->brand = filled($state) ? trim((string) $state) : null;
                }),

            // Model
            ImportColumn::make('model')
                ->fillRecordUsing(function (Product $record, $state) {
                    $record->model = filled($state) ? trim((string) $state) : null;
                }),

            // Description
            ImportColumn::make('description')
                ->fillRecordUsing(function (Product $record, $state) {
                    $record->description = filled($state) ? (string) $state : null;
                }),

            // Relasi Supplier: by code > by name (auto-create jika salah satunya ada)
            ImportColumn::make('supplier_code')
                ->fillRecordUsing(function (Product $record, $state, array $row) {
                    $code = filled($state) ? trim((string) $state) : null;
                    $name = filled($row['supplier_name'] ?? null) ? trim((string) $row['supplier_name']) : null;

                    if (! $code && ! $name) {
                        // tidak set supplier
                        $record->supplier_id = null;
                        return;
                    }

                    $supplierQuery = Supplier::query();

                    // prioritas cari by code
                    if ($code) {
                        $supplier = $supplierQuery->where('code', $code)->first();
                        if (! $supplier) {
                            // auto-create supplier dengan code
                            $supplier = Supplier::create([
                                'code'      => $code,
                                'name'      => $name ?: Str::headline($code),
                                'is_active' => true,
                            ]);
                        }
                        $record->supplier_id = $supplier->id;
                        return;
                    }

                    // cari by name
                    if ($name) {
                        $supplier = $supplierQuery->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
                        if (! $supplier) {
                            // auto-create supplier dengan name (buat code singkat)
                            $supplier = Supplier::create([
                                'code'      => Str::upper(Str::slug(mb_substr($name, 0, 16), '-')),
                                'name'      => $name,
                                'is_active' => true,
                            ]);
                        }
                        $record->supplier_id = $supplier->id;
                        return;
                    }
                }),

            // is_active
            ImportColumn::make('is_active')
                ->fillRecordUsing(function (Product $record, $state) {
                    // normalisasi boolean
                    $truthy = ['1', 1, true, 'true', 'yes', 'aktif', 'active', 'on'];
                    $falsy  = ['0', 0, false, 'false', 'no', 'nonaktif', 'inactive', 'off'];

                    if (in_array($state, $truthy, true)) {
                        $record->is_active = true;
                    } elseif (in_array($state, $falsy, true)) {
                        $record->is_active = false;
                    } else {
                        // default jika kosong → aktif
                        $record->is_active = $record->is_active ?? true;
                    }
                }),
        ];
    }

    /**
     * Notifikasi selesai import.
     */
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and '
            . Number::format($import->successful_rows) . ' '
            . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
