<?php

namespace App\Filament\Imports;

use App\Models\Inventory\Warehouse;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WarehouseImporter extends Importer
{
    protected static ?string $model = Warehouse::class;

    /**
     * Definisi kolom, validasi, dan normalisasi input.
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:50'])
                ->exampleHeader('code'),

            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->exampleHeader('name'),

            ImportColumn::make('address')
                ->rules(['nullable', 'string', 'max:500'])
                ->exampleHeader('address'),

            ImportColumn::make('district')
                ->rules(['nullable', 'string', 'max:255'])
                ->exampleHeader('district'),

            ImportColumn::make('city')
                ->rules(['nullable', 'string', 'max:255'])
                ->exampleHeader('city'),

            ImportColumn::make('province')
                ->rules(['nullable', 'string', 'max:255'])
                ->exampleHeader('province'),

            ImportColumn::make('postal_code')
                ->castStateUsing(function ($v) {
                    $v = trim((string) $v);
                    return $v !== '' ? $v : null;
                })
                ->rules(['nullable', 'string', 'max:20'])
                ->exampleHeader('postal_code'),

            ImportColumn::make('lat')
                ->numeric()
                ->castStateUsing(fn ($v) => self::normalizeDecimal($v))
                ->rules(['nullable', 'numeric', 'between:-90,90'])
                ->exampleHeader('lat'),

            ImportColumn::make('lng')
                ->numeric()
                ->castStateUsing(fn ($v) => self::normalizeDecimal($v))
                ->rules(['nullable', 'numeric', 'between:-180,180'])
                ->exampleHeader('lng'),

            ImportColumn::make('phone')
                ->castStateUsing(function ($state) {
                    if (blank($state)) return null;
                    $state = trim((string) $state);
                    // hapus spasi; pertahankan '+' hanya di awal; buang karakter non-digit lainnya
                    $state = Str::of($state)
                        ->replaceMatches('/\s+/', '')
                        ->replaceMatches('/(?!^\+)[^\d]/', '')
                        ->toString();
                    return $state !== '' ? $state : null;
                })
                ->rules(['nullable', 'string', 'max:32'])
                ->exampleHeader('phone'),

            ImportColumn::make('is_active')
                ->boolean() // aktifkan casting boolean dasar
                ->castStateUsing(fn ($v) => self::toBoolean($v))
                ->rules(['nullable', Rule::in([0,1,true,false])])
                ->exampleHeader('is_active'),
        ];
    }

    /**
     * Idempotent berdasarkan code. Jika ada yang soft-deleted, kita restore.
     */
    public function resolveRecord(): Warehouse
    {
        $code = $this->data['code'] ?? null;

        // cari termasuk yang terhapus
        $existing = Warehouse::withTrashed()->firstWhere('code', $code);

        if ($existing) {
            if ($existing->trashed()) {
                // restore lebih awal agar tidak gagal oleh unique constraint internal
                $existing->restore();
            }
            return $existing;
        }

        return new Warehouse(['code' => $code]);
    }

    /**
     * (Opsional) Contoh baris CSV untuk panduan tim.
     */
    public static function getExampleRows(): array
    {
        return [[
            'code'        => 'WH-001',
            'name'        => 'Gudang Utama Bandung',
            'address'     => 'Jl. Raya Industri No. 10',
            'district'    => 'Cimahi Selatan',
            'city'        => 'Bandung',
            'province'    => 'Jawa Barat',
            'postal_code' => '40534',
            'lat'         => '-6,914744',   // koma juga didukung
            'lng'         => '107.609810',
            'phone'       => '+62222000000',
            'is_active'   => 'ya',          // yes/ya/aktif/true/1 => true
        ]];
    }

    /**
     * Notifikasi ringkas setelah impor selesai.
     */
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your warehouse import has completed and '
            . Number::format($import->successful_rows) . ' '
            . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    /* ==================== Helpers ==================== */

    /**
     * Normalisasi desimal (dukung koma sebagai pemisah).
     */
    protected static function normalizeDecimal($value): ?float
    {
        if (blank($value)) return null;

        $value = trim((string) $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/\s+/', '', $value);

        if (!is_numeric($value)) return null;

        return (float) $value;
    }

    /**
     * Terima beragam input boolean (ya/yes/aktif/true/1 → true, dst).
     */
    protected static function toBoolean($value): ?bool
    {
        if ($value === null || $value === '') return null;
        $v = strtolower(trim((string) $value));

        $truthy = ['1','true','yes','ya','y','aktif','active','on'];
        $falsy  = ['0','false','no','tidak','t','nonaktif','inactive','off'];

        if (in_array($v, $truthy, true)) return true;
        if (in_array($v, $falsy, true))  return false;
        if (is_numeric($v)) return ((int) $v) === 1;

        return null;
    }
}
