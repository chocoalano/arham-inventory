<?php

namespace App\Filament\Imports;

use App\Models\Inventory\Transaction;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class TransactionImporter extends Importer
{
    protected static ?string $model = Transaction::class;

    /**
     * Definisi kolom yang bisa diimpor.
     * Gunakan heading seperti pada label agar lebih ramah non-teknis,
     * namun tetap menerima heading "mentah" (via ->guess(['...'])).
     */
    public static function getColumns(): array
    {
        return [
            // Unik & kunci pencocokan
            ImportColumn::make('reference_number')
                ->label('Nomor Referensi')
                ->required()
                ->rules(['string', 'max:64'])
                ->example('TRX-240001'),

            // Enum / Jenis transaksi
            ImportColumn::make('type')
                ->label('Jenis Transaksi')
                ->required()
                ->rules(['in:penjualan,pemindahan,pengembalian'])
                ->example('penjualan'),

            // Tanggal transaksi
            ImportColumn::make('transaction_date')
                ->label('Tanggal Transaksi')
                ->required()
                ->rules(['date'])
                ->castStateUsing(fn ($state) => self::parseDate($state))
                ->example('2025-08-12 14:30'),

            // Gudang Asal (coba by code dulu, fallback ke name)
            ImportColumn::make('source_warehouse')
                ->label('Gudang Asal (code/name)')
                ->fillUsing(function (Transaction $record, $state) {
                    $record->source_warehouse_id = self::findWarehouseIdByCodeOrName($state);
                })
                ->example('GDG-JKT-01'),

            // Gudang Tujuan (coba by code dulu, fallback ke name)
            ImportColumn::make('destination_warehouse')
                ->label('Gudang Tujuan (code/name)')
                ->fillUsing(function (Transaction $record, $state) {
                    $record->destination_warehouse_id = self::findWarehouseIdByCodeOrName($state);
                })
                ->example('GDG-SBY-01'),

            // Data customer (opsional)
            ImportColumn::make('customer_name')
                ->label('Nama Pelanggan')
                ->rules(['nullable', 'string', 'max:150'])
                ->example('PT Maju Jaya'),

            ImportColumn::make('customer_phone')
                ->label('Telepon Pelanggan')
                ->rules(['nullable', 'string', 'max:32'])
                ->example('0812-3456-7890'),

            ImportColumn::make('customer_full_address')
                ->label('Alamat Pelanggan')
                ->rules(['nullable', 'string'])
                ->example('Jl. Merpati No. 10, Surabaya'),

            // Ringkasan nilai
            ImportColumn::make('item_count')
                ->label('Jumlah Item')
                ->rules(['nullable', 'integer', 'min:0'])
                ->castStateUsing(fn ($s) => is_numeric($s) ? (int) $s : 0)
                ->example('5'),

            ImportColumn::make('grand_total')
                ->label('Total Transaksi')
                ->rules(['nullable', 'numeric', 'min:0'])
                ->castStateUsing(fn ($s) => self::toDecimal($s))
                ->example('1500000'),

            // Status
            ImportColumn::make('status')
                ->label('Status (draft/posted/cancelled)')
                ->rules(['nullable', 'in:draft,posted,cancelled'])
                ->default('draft')
                ->example('draft'),

            // Waktu posted (opsional; jika diisi dan valid → status otomatis 'posted' bila belum diatur)
            ImportColumn::make('posted_at')
                ->label('Tanggal Posting')
                ->rules(['nullable', 'date'])
                ->castStateUsing(fn ($state) => self::parseDate($state))
                ->fillUsing(function (Transaction $record, $state) {
                    $record->posted_at = $state;
                    if ($state && empty($record->status)) {
                        $record->status = 'posted';
                    }
                })
                ->example('2025-08-12 16:00'),

            // Dibuat oleh (email user)
            ImportColumn::make('created_by_email')
                ->label('Email Pembuat (User)')
                ->rules(['nullable', 'email'])
                ->fillUsing(function (Transaction $record, $state) {
                    if (!$state) {
                        return;
                    }
                    $user = User::query()->where('email', $state)->first();
                    if ($user) {
                        $record->created_by = $user->id;
                    }
                })
                ->example('staff@perusahaan.com'),

            // Catatan
            ImportColumn::make('remarks')
                ->label('Catatan')
                ->rules(['nullable', 'string'])
                ->example('Pengiriman prioritas, packing fragile.'),
        ];
    }

    /**
     * Idempotent: jika reference_number sudah ada → update, kalau tidak → create.
     */
    public function resolveRecord(): Transaction
    {
        return Transaction::firstOrNew([
            'reference_number' => $this->data['reference_number'] ?? null,
        ]);
    }

    /**
     * Normalisasi / post-processing sebelum simpan (opsional).
     */
    public function beforeSave(): void
    {
        // Pastikan status terisi default
        if (empty($this->record->status)) {
            $this->record->status = 'draft';
        }

        // Jika posted_at terisi dan status belum 'posted', set jadi 'posted'
        if (!empty($this->record->posted_at) && $this->record->status === 'draft') {
            $this->record->status = 'posted';
        }

        // Sanit angka grand_total (jika masih null)
        if ($this->record->grand_total === null) {
            $this->record->grand_total = 0;
        }

        // Sanit jumlah item (jika null)
        if ($this->record->item_count === null) {
            $this->record->item_count = 0;
        }
    }

    /**
     * Notifikasi selesai impor.
     */
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import transaksi selesai. '
            . Number::format($import->successful_rows) . ' '
            . str('baris')->plural($import->successful_rows) . ' berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '
                . Number::format($failedRowsCount) . ' '
                . str('baris')->plural($failedRowsCount)
                . ' gagal diimpor.';
        }

        return $body;
    }

    /**
     * (Opsional) Contoh heading CSV agar user mudah mengikuti.
     * Kamu bisa menampilkan ini di UI atau dokumentasi internal.
     */
    public static function getExampleCsvHeader(): array
    {
        return [
            'Nomor Referensi',
            'Jenis Transaksi',
            'Tanggal Transaksi',
            'Gudang Asal (code/name)',
            'Gudang Tujuan (code/name)',
            'Nama Pelanggan',
            'Telepon Pelanggan',
            'Alamat Pelanggan',
            'Jumlah Item',
            'Total Transaksi',
            'Status (draft/posted/cancelled)',
            'Tanggal Posting',
            'Email Pembuat (User)',
            'Catatan',
        ];
    }

    /* =========================
     * Helpers
     * ========================= */

    private static function parseDate(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        // Dukung beberapa format umum.
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            Carbon::RFC3339,
            Carbon::ISO8601,
        ];

        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, (string) $value);
                if ($dt !== false) {
                    return $dt;
                }
            } catch (\Throwable) {
                // continue
            }
        }

        // Fallback parse bebas.
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function toDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Hilangkan pemisah ribuan umum: ".", "," lalu normalisasi desimal.
        $raw = (string) $value;

        // Jika berbentuk "1.234.567,89" (format ID) → ganti '.' hilang dan ',' jadi '.'
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $raw)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            // Jika berbentuk "1,234,567.89" → hilangkan ',' ribuan.
            $raw = str_replace(',', '', $raw);
        }

        return (float) $raw;
    }

    private static function findWarehouseIdByCodeOrName(?string $needle): ?int
    {
        if (!$needle) {
            return null;
        }

        $needle = trim($needle);

        // Prioritas cari by code (unik), fallback ke name.
        $byCode = Warehouse::query()->where('code', $needle)->value('id');
        if ($byCode) {
            return (int) $byCode;
        }

        $byName = Warehouse::query()->where('name', $needle)->value('id');
        return $byName ? (int) $byName : null;
    }
}
