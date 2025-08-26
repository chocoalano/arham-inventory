<?php

namespace App\Filament\Imports;

use App\Models\Inventory\Supplier;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupplierImporter extends Importer
{
    protected static ?string $model = Supplier::class;

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

            ImportColumn::make('contact_name')
                ->rules(['nullable', 'string', 'max:255'])
                ->exampleHeader('contact_name'),

            ImportColumn::make('phone')
                ->castStateUsing(function ($state) {
                    if (blank($state)) return null;
                    $state = trim((string) $state);
                    // hapus spasi, simpan '+' hanya di awal
                    $state = Str::of($state)
                        ->replaceMatches('/\s+/', '')
                        ->replaceMatches('/(?!^\+)[^\d]/', '')
                        ->toString();
                    return $state !== '' ? $state : null;
                })
                ->rules(['nullable', 'string', 'max:32'])
                ->exampleHeader('phone'),

            ImportColumn::make('email')
                ->castStateUsing(fn ($v) => blank($v) ? null : strtolower(trim((string) $v)))
                ->rules(['nullable', 'email:rfc,dns', 'max:255'])
                ->exampleHeader('email'),

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

            ImportColumn::make('is_active')
                ->boolean()
                ->castStateUsing(fn ($v) => self::toBoolean($v))
                ->rules(['nullable', Rule::in([0,1,true,false])])
                ->exampleHeader('is_active'),
        ];
    }

    public function resolveRecord(): Supplier
    {
        return Supplier::firstOrNew([
            'code' => $this->data['code'],
        ]);
    }

    public static function getExampleRows(): array
    {
        return [[
            'code'         => 'SUP-0001',
            'name'         => 'PT Sumber Niaga',
            'contact_name' => 'Budi Santoso',
            'phone'        => '+6281234567890',
            'email'        => 'procurement@sumberniaga.co.id',
            'address'      => 'Jl. Mawar No. 10',
            'district'     => 'Cicendo',
            'city'         => 'Bandung',
            'province'     => 'Jawa Barat',
            'postal_code'  => '40172',
            'lat'          => '-6,914744',
            'lng'          => '107.609810',
            'is_active'    => 'ya',
        ]];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your supplier import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    // Helpers
    protected static function normalizeDecimal($value): ?float
    {
        if (blank($value)) return null;
        $value = trim((string) $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/\s+/', '', $value);
        if (!is_numeric($value)) return null;
        return (float) $value;
    }

    protected static function toBoolean($value): ?bool
    {
        if ($value === null || $value === '') return null;
        $v = strtolower(trim((string) $value));
        $truthy = ['1','true','yes','ya','y','aktif','active','on'];
        $falsy  = ['0','false','no','tidak','t','nonaktif','inactive','off'];
        if (in_array($v, $truthy, true)) return true;
        if (in_array($v, $falsy, true)) return false;
        if (is_numeric($v)) return ((int) $v) === 1;
        return null;
    }
}
