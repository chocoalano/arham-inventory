<?php

namespace App\Filament\Imports;

use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class InventoryMovementImporter extends Importer
{
    protected static ?string $model = InventoryMovement::class;

    /**
     * Kolom-kolom yang didukung:
     * - Bisa pakai ID langsung, atau CODE/SKU untuk resolusi relasi.
     */
    public static function getColumns(): array
    {
        return [
            // --- TRANSACTION ---
            ImportColumn::make('transaction_id')
                ->label('transaction_id')
                ->rules(['nullable', 'integer', 'exists:transactions,id'])
                ->exampleHeader('transaction_id'),

            // --- FROM WAREHOUSE ---
            ImportColumn::make('from_warehouse_id')
                ->label('from_warehouse_id')
                ->rules(['nullable', 'integer', 'exists:warehouses,id'])
                ->exampleHeader('from_warehouse_id'),

            ImportColumn::make('from_warehouse_code')
                ->label('from_warehouse_code')
                ->rules(['nullable', 'string', 'max:50', 'exists:warehouses,code'])
                ->castStateUsing(fn ($v) => self::strOrNull($v))
                ->fillRecordUsing(function (InventoryMovement $record, $state) {
                    if ($state) {
                        $wh = Warehouse::withTrashed()->firstWhere('code', $state);
                        if ($wh && $wh->trashed()) $wh->restore();
                        $record->from_warehouse_id = $wh?->id;
                    }
                })
                ->exampleHeader('from_warehouse_code'),

            // --- TO WAREHOUSE ---
            ImportColumn::make('to_warehouse_id')
                ->label('to_warehouse_id')
                ->rules(['nullable', 'integer', 'exists:warehouses,id'])
                ->exampleHeader('to_warehouse_id'),

            ImportColumn::make('to_warehouse_code')
                ->label('to_warehouse_code')
                ->rules(['nullable', 'string', 'max:50', 'exists:warehouses,code'])
                ->castStateUsing(fn ($v) => self::strOrNull($v))
                ->fillRecordUsing(function (InventoryMovement $record, $state) {
                    if ($state) {
                        $wh = Warehouse::withTrashed()->firstWhere('code', $state);
                        if ($wh && $wh->trashed()) $wh->restore();
                        $record->to_warehouse_id = $wh?->id;
                    }
                })
                ->exampleHeader('to_warehouse_code'),

            // --- PRODUCT VARIANT ---
            ImportColumn::make('product_variant_id')
                ->label('product_variant_id')
                ->rules(['nullable', 'integer', 'exists:product_variants,id'])
                ->exampleHeader('product_variant_id'),

            ImportColumn::make('product_variant_sku_variant')
                ->label('product_variant_sku_variant')
                ->rules(['nullable', 'string', 'max:64', 'exists:product_variants,sku_variant'])
                ->castStateUsing(fn ($v) => self::strOrNull($v))
                ->fillRecordUsing(function (InventoryMovement $record, $state) {
                    if ($state) {
                        $pv = ProductVariant::withTrashed()->firstWhere('sku_variant', $state);
                        if ($pv && $pv->trashed()) $pv->restore();
                        $record->product_variant_id = $pv?->id;
                    }
                })
                ->exampleHeader('product_variant_sku_variant'),

            // --- PAYLOAD ---
            ImportColumn::make('qty_change')
                ->label('qty_change')
                ->rules(['required', 'integer', 'not_in:0'])
                ->castStateUsing(fn ($v) => is_numeric($v) ? (int) $v : null)
                ->exampleHeader('qty_change'),

            ImportColumn::make('type')
                ->label('type')
                // Jika punya daftar tipe baku (mis. ['in','out','transfer']) aktifkan Rule::in
                // ->rules(['required', 'string', Rule::in(['in','out','transfer'])])
                ->rules(['required', 'string', 'max:50'])
                ->castStateUsing(fn ($v) => self::strOrNull($v))
                ->exampleHeader('type'),

            ImportColumn::make('occurred_at')
                ->label('occurred_at')
                ->rules(['required'])
                ->castStateUsing(fn ($v) => self::parseDateTime($v)) // hasil CarbonImmutable|null
                ->fillRecordUsing(function (InventoryMovement $record, $state) {
                    // simpan sebagai string datetime sesuai casts model
                    $record->occurred_at = optional($state)?->toDateTimeString();
                })
                ->exampleHeader('occurred_at'),

            ImportColumn::make('remarks')
                ->label('remarks')
                ->rules(['nullable', 'string', 'max:500'])
                ->castStateUsing(fn ($v) => self::strOrNull($v))
                ->exampleHeader('remarks'),

            // --- CREATOR ---
            ImportColumn::make('created_by')
                ->label('created_by')
                ->rules(['nullable', 'integer', 'exists:users,id'])
                ->exampleHeader('created_by'),

            ImportColumn::make('created_by_email')
                ->label('created_by_email')
                ->rules(['nullable', 'email:rfc,dns', 'max:255', 'exists:users,email'])
                ->castStateUsing(fn ($v) => self::emailOrNull($v))
                ->fillRecordUsing(function (InventoryMovement $record, $state) {
                    if ($state) {
                        $u = User::where('email', $state)->first();
                        $record->created_by = $u?->id;
                    }
                })
                ->exampleHeader('created_by_email'),
        ];
    }

    /**
     * Upsert berbasis "natural key".
     */
    public function resolveRecord(): InventoryMovement
    {
        // Ambil nilai yang sudah dicasting oleh ImportColumn
        $trxId   = $this->data['transaction_id']           ?? null;
        $fromId  = $this->data['from_warehouse_id']        ?? null;
        $toId    = $this->data['to_warehouse_id']          ?? null;
        $pvId    = $this->data['product_variant_id']       ?? null;
        $qty     = $this->data['qty_change']               ?? null;
        $type    = $this->data['type']                     ?? null;
        $occurAt = $this->data['occurred_at']              ?? null; // sudah di-cast ke Carbon di castStateUsing()

        // Pastikan created_by terisi default auth jika masih kosong
        if (!($this->data['created_by'] ?? null) && auth()->id()) {
            $this->data['created_by'] = auth()->id();
        }

        // Buat key idempotent (abaikan from/to untuk kasus transfer in/out umum)
        // Jika kamu butuh memastikan from/to jadi bagian key, tambahkan keduanya ke where-cause.
        $query = InventoryMovement::withTrashed()->where([
            'transaction_id'     => $trxId,
            'product_variant_id' => $pvId,
            'qty_change'         => $qty,
            'type'               => $type,
        ]);

        if ($occurAt instanceof CarbonImmutable) {
            $query->where('occurred_at', $occurAt->toDateTimeString());
        }

        $existing = $query->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            return $existing;
        }

        return new InventoryMovement([
            'transaction_id'     => $trxId,
            'from_warehouse_id'  => $fromId,
            'to_warehouse_id'    => $toId,
            'product_variant_id' => $pvId,
            'qty_change'         => $qty,
            'type'               => $type,
            'occurred_at'        => $occurAt instanceof CarbonImmutable ? $occurAt->toDateTimeString() : $occurAt,
            'remarks'            => $this->data['remarks'] ?? null,
            'created_by'         => $this->data['created_by'] ?? null,
        ]);
    }

    public static function getExampleRows(): array
    {
        return [[
            'transaction_id'             => 1024,
            'from_warehouse_code'        => 'WH-001',
            'to_warehouse_code'          => 'WH-002',
            'product_variant_sku_variant'=> 'SKU-MATCHA-M-001',
            'qty_change'                 => -10,
            'type'                       => 'transfer',
            'occurred_at'                => '2025-08-20 14:30:00',
            'remarks'                    => 'Transfer ke gudang pusat',
            'created_by_email'           => 'ops@sas.co.id',
        ]];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your inventory movement import has completed and '
            . Number::format($import->successful_rows) . ' '
            . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' '
                . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    /* ================= Helpers ================= */

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

    protected static function parseDateTime($value): ?CarbonImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }
        if (is_numeric($value)) {
            // dukung Excel serial date
            // 25569 = 1970-01-01; konversi day fraction → seconds
            return CarbonImmutable::createFromTimestampUTC(((int)$value - 25569) * 86400);
        }

        $value = trim((string) $value);
        if ($value === '') return null;

        // Coba beberapa pola umum
        $patterns = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            CarbonImmutable::ISO_FORMAT_REGEXP,
            \DateTime::ATOM,
            \DateTime::RFC3339_EXTENDED,
        ];

        foreach ($patterns as $fmt) {
            try {
                $dt = CarbonImmutable::createFromFormat($fmt, $value);
                if ($dt !== false) return $dt;
            } catch (\Throwable) {
                // try next
            }
        }

        // terakhir: parser bebas
        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
