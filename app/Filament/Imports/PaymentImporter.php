<?php

namespace App\Filament\Imports;

use App\Models\Inventory\Payment;
use App\Models\Inventory\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

class PaymentImporter extends Importer
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            // --- INVOICE ---
            ImportColumn::make('invoice_id')
                ->label('invoice_id')
                ->rules(['nullable', 'integer', 'exists:invoices,id'])
                ->exampleHeader('invoice_id'),

            ImportColumn::make('invoice_number')
                ->label('invoice_number')
                ->rules(['nullable', 'string', 'max:100', 'exists:invoices,invoice_number'])
                ->castStateUsing(fn ($v) => self::strOrNull($v))
                ->fillRecordUsing(function (Payment $record, $state) {
                    if ($state) {
                        $inv = Invoice::withTrashed()->firstWhere('invoice_number', $state);
                        if ($inv && $inv->trashed()) $inv->restore();
                        $record->invoice_id = $inv?->id;
                    }
                })
                ->exampleHeader('invoice_number'),

            // --- PAYLOAD ---
            ImportColumn::make('amount')
                ->label('amount')
                ->rules(['required', 'numeric', 'min:0.01'])
                ->castStateUsing(fn ($v) => self::decimal($v))
                ->exampleHeader('amount'),

            ImportColumn::make('method')
                ->label('method')
                // Jika punya daftar metode baku, aktifkan Rule::in([...])
                ->rules(['required', 'string', 'max:50'])
                ->castStateUsing(fn ($v) => self::strOrNull($v))
                ->exampleHeader('method'),

            ImportColumn::make('reference_no')
                ->label('reference_no')
                ->rules(['nullable', 'string', 'max:100'])
                ->castStateUsing(fn ($v) => self::strOrNull($v))
                ->exampleHeader('reference_no'),

            ImportColumn::make('paid_at')
                ->label('paid_at')
                ->rules(['required'])
                ->castStateUsing(fn ($v) => self::parseDateTime($v)) // CarbonImmutable|null
                ->fillRecordUsing(fn (Payment $r, $state) => $r->paid_at = optional($state)?->toDateTimeString())
                ->exampleHeader('paid_at'),

            ImportColumn::make('notes')
                ->label('notes')
                ->rules(['nullable', 'string', 'max:500'])
                ->castStateUsing(fn ($v) => self::strOrNull($v))
                ->exampleHeader('notes'),

            // --- RECEIVER ---
            ImportColumn::make('received_by')
                ->label('received_by')
                ->rules(['nullable', 'integer', 'exists:users,id'])
                ->exampleHeader('received_by'),

            ImportColumn::make('receiver_email')
                ->label('receiver_email')
                ->rules(['nullable', 'email:rfc,dns', 'max:255', 'exists:users,email'])
                ->castStateUsing(fn ($v) => self::emailOrNull($v))
                ->fillRecordUsing(function (Payment $record, $state) {
                    if ($state) {
                        $u = User::where('email', $state)->first();
                        $record->received_by = $u?->id;
                    }
                })
                ->exampleHeader('receiver_email'),
        ];
    }

    /**
     * Upsert berbasis natural key:
     * invoice_id + paid_at + amount + method + reference_no
     */
    public function resolveRecord(): Payment
    {
        $invoiceId = $this->data['invoice_id'] ?? null;
        $amount    = $this->data['amount'] ?? null;
        $method    = $this->data['method'] ?? null;
        $ref       = $this->data['reference_no'] ?? null;
        $paidAt    = $this->data['paid_at'] ?? null; // sudah di-cast ke Carbon di castStateUsing()

        $query = Payment::withTrashed()->where([
            'invoice_id'   => $invoiceId,
            'amount'       => $amount,
            'method'       => $method,
            'reference_no' => $ref,
        ]);

        if ($paidAt instanceof CarbonImmutable) {
            $query->where('paid_at', $paidAt->toDateTimeString());
        }

        $existing = $query->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            return $existing;
        }

        return new Payment([
            'invoice_id'   => $invoiceId,
            'amount'       => $amount,
            'method'       => $method,
            'reference_no' => $ref,
            'paid_at'      => $paidAt instanceof CarbonImmutable ? $paidAt->toDateTimeString() : $paidAt,
            'notes'        => $this->data['notes'] ?? null,
            'received_by'  => $this->data['received_by'] ?? null,
        ]);
    }

    public static function getExampleRows(): array
    {
        return [[
            'invoice_number' => 'INV-2025-000123',
            'amount'         => '1.250.000,00',   // format lokal didukung
            'method'         => 'transfer',
            'reference_no'   => 'TRX-992233',
            'paid_at'        => '2025-08-22 15:45:00',
            'notes'          => 'Pembayaran termin 1',
            'receiver_email' => 'kasir@sas.co.id',
        ]];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your payment import has completed and '
              . Number::format($import->successful_rows) . ' '
              . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                   . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    /* ===== Helpers ===== */

    protected static function strOrNull($v): ?string
    {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : $v;
    }

    protected static function emailOrNull($v): ?string
    {
        $v = strtolower(trim((string) ($v ?? '')));
        return $v === '' ? null : $v;
    }

    protected static function decimal($v): ?float
    {
        if ($v === null || $v === '') return null;
        $v = preg_replace('/\s+/', '', (string) $v);
        // Hilangkan semua pemisah ribuan titik; ubah koma terakhir jadi titik
        // Contoh "1.234.567,89" => "1234567.89"
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
        return is_numeric($v) ? (float) $v : null;
    }

    protected static function parseDateTime($value): ?CarbonImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }
        if (is_numeric($value)) {
            // Excel serial date
            return CarbonImmutable::createFromTimestampUTC(((int)$value - 25569) * 86400);
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
