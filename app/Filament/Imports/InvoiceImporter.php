<?php

namespace App\Filament\Imports;

use App\Models\Inventory\Invoice;
use App\Models\Inventory\Transaction;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

class InvoiceImporter extends Importer
{
    protected static ?string $model = Invoice::class;

    public static function getColumns(): array
    {
        return [
            // Identitas utama
            ImportColumn::make('invoice_number')
                ->label('invoice_number')
                ->rules(['required', 'string', 'max:100'])
                ->exampleHeader('invoice_number'),

            ImportColumn::make('transaction_id')
                ->label('transaction_id')
                ->rules(['nullable', 'integer', 'exists:transactions,id'])
                ->exampleHeader('transaction_id'),

            // Tanggal
            ImportColumn::make('issued_at')
                ->label('issued_at')
                ->rules(['required'])
                ->castStateUsing(fn ($v) => self::parseDateTime($v))
                ->fillRecordUsing(fn (Invoice $r, $state) => $r->issued_at = optional($state)?->toDateTimeString())
                ->exampleHeader('issued_at'),

            ImportColumn::make('due_at')
                ->label('due_at')
                ->rules(['nullable'])
                ->castStateUsing(fn ($v) => self::parseDateTime($v))
                ->fillRecordUsing(fn (Invoice $r, $state) => $r->due_at = optional($state)?->toDateTimeString())
                ->exampleHeader('due_at'),

            // Angka (mendukung koma desimal)
            ImportColumn::make('subtotal')
                ->label('subtotal')
                ->rules(['required', 'numeric', 'min:0'])
                ->castStateUsing(fn ($v) => self::decimal($v)),

            ImportColumn::make('discount_total')
                ->label('discount_total')
                ->rules(['nullable', 'numeric', 'min:0'])
                ->castStateUsing(fn ($v) => self::decimal($v)),

            ImportColumn::make('tax_total')
                ->label('tax_total')
                ->rules(['nullable', 'numeric', 'min:0'])
                ->castStateUsing(fn ($v) => self::decimal($v)),

            ImportColumn::make('shipping_fee')
                ->label('shipping_fee')
                ->rules(['nullable', 'numeric', 'min:0'])
                ->castStateUsing(fn ($v) => self::decimal($v)),

            ImportColumn::make('total_amount')
                ->label('total_amount')
                ->rules(['required', 'numeric', 'min:0'])
                ->castStateUsing(fn ($v) => self::decimal($v)),

            ImportColumn::make('paid_amount')
                ->label('paid_amount')
                ->rules(['nullable', 'numeric', 'min:0'])
                ->castStateUsing(fn ($v) => self::decimal($v)),

            ImportColumn::make('is_paid')
                ->label('is_paid')
                ->boolean()
                ->castStateUsing(fn ($v) => self::toBoolean($v))
                ->rules(['nullable', Rule::in([0,1,true,false])]),
        ];
    }

    /**
     * Upsert by invoice_number (natural key). Restore jika soft-deleted.
     */
    public function resolveRecord(): Invoice
    {
        $no = $this->data['invoice_number'] ?? null;

        $existing = Invoice::withTrashed()->firstWhere('invoice_number', $no);
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            return $existing;
        }

        return new Invoice(['invoice_number' => $no]);
    }

    public static function getExampleRows(): array
    {
        return [[
            'invoice_number'  => 'INV-2025-000123',
            'transaction_id'  => 12045,
            'issued_at'       => '2025-08-20 10:00:00',
            'due_at'          => '2025-09-04 23:59:59',
            'subtotal'        => '1.250.000,00',   // koma/titik akan dinormalisasi
            'discount_total'  => '50.000',
            'tax_total'       => '120.000',
            'shipping_fee'    => '25.000',
            'total_amount'    => '1.345.000',
            'paid_amount'     => '500.000',
            'is_paid'         => 'no',             // yes/ya/true/1 → true
        ]];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your invoice import has completed and '
              . Number::format($import->successful_rows) . ' '
              . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                   . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    /* ===== Helpers ===== */

    protected static function decimal($v): ?float
    {
        if ($v === null || $v === '') return null;
        // hapus spasi, seragamkan pemisah ribuan/desimal ID → en
        $v = preg_replace('/\s+/', '', (string) $v);
        // hilangkan pemisah ribuan (titik atau koma), lalu gunakan titik sebagai desimal
        // contoh: "1.234.567,89" → "1234567.89"
        $v = str_replace(['.', ','], ['', '.'], preg_replace('/\.(?=.*\.)/', '', str_replace(',', '.', $v)));
        // fallback sederhana: ganti koma ke titik lalu buang spasi
        $v = str_replace(',', '.', $v);
        return is_numeric($v) ? (float) $v : null;
    }

    protected static function toBoolean($value): ?bool
    {
        if ($value === null || $value === '') return null;
        $v = strtolower(trim((string) $value));
        $truthy = ['1','true','yes','ya','y','paid','lunas'];
        $falsy  = ['0','false','no','tidak','t','unpaid','belum'];
        if (in_array($v, $truthy, true)) return true;
        if (in_array($v, $falsy, true))  return false;
        if (is_numeric($v)) return ((int) $v) === 1;
        return null;
    }

    protected static function parseDateTime($value): ?CarbonImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }
        if (is_numeric($value)) {
            // dukung Excel serial date
            return CarbonImmutable::createFromTimestampUTC(((int) $value - 25569) * 86400);
        }
        $value = trim((string) $value);
        if ($value === '') return null;

        $patterns = [
            'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
            'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
            \DateTime::ATOM, \DateTime::RFC3339_EXTENDED,
        ];
        foreach ($patterns as $fmt) {
            try {
                $dt = CarbonImmutable::createFromFormat($fmt, $value);
                if ($dt !== false) return $dt;
            } catch (\Throwable) {}
        }
        try { return CarbonImmutable::parse($value); } catch (\Throwable) { return null; }
    }
}
