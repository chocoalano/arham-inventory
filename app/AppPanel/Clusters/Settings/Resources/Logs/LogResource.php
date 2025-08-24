<?php

namespace App\AppPanel\Clusters\Settings\Resources\Logs;

use App\AppPanel\Clusters\Settings\Resources\Logs\Pages\ManageLogs;
use App\AppPanel\Clusters\Settings\SettingsCluster;
use App\Models\Log;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

class LogResource extends Resource
{
    protected static ?string $model = Log::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $recordTitleAttribute = 'log_name';

    public static function infolist(Schema $schema): Schema
    {
        // helper: normalisasi properties → array php
        $normalizeProps = function ($props): array {
            if ($props instanceof Arrayable) {
                $props = $props->toArray();
            } elseif (is_string($props)) {
                $decoded = json_decode($props, true);
                $props = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            } elseif (!is_array($props)) {
                $props = [];
            }
            return $props;
        };

        // helper: ubah array menjadi array<string,string> aman untuk KeyValueEntry
        $toStringMap = function (array $bag): array {
            $hidden = ['password', 'remember_token'];
            $moneyFields = ['price', 'cost_price', 'total', 'amount', 'subtotal'];
            $intFields = ['qty', 'reserved_qty', 'warehouse_id', 'product_variant_id', 'stock'];

            $fmt = function (string $k, $v) use ($moneyFields, $intFields): string {
                if (is_null($v))
                    return 'null';

                if (in_array($k, $moneyFields, true) && is_numeric($v)) {
                    return 'Rp ' . number_format((float) $v, 0, ',', '.');
                }

                if (in_array($k, $intFields, true) && is_numeric($v)) {
                    return number_format((float) $v, 0, ',', '.');
                }

                return is_scalar($v)
                    ? (string) $v
                    : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            };

            $out = [];
            foreach ($bag as $k => $v) {
                $k = (string) $k;
                $out[$k] = in_array($k, $hidden, true) ? '(disembunyikan)' : $fmt($k, $v);
            }
            return $out;
        };
        return $schema
            ->components([
                Section::make('Detail Perubahan')->schema([
                    // Selalu ada untuk debug (tidak bikin error)
                    TextEntry::make('properties_debug')
                        ->label('Debug Properties (raw)')
                        ->state(function ($record) use ($normalizeProps) {
                            $arr = $normalizeProps($record->properties ?? null);
                            return $arr === [] ? '(kosong)' : json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        })
                        ->copyable()
                        ->prose()
                        ->columnSpanFull(),

                    // Langsung OLD
                    KeyValueEntry::make('properties_old')
                        ->label('Sebelumnya (old)')
                        ->state(function ($record) use ($normalizeProps, $toStringMap) {
                            $props = $normalizeProps($record->properties ?? null);
                            return $toStringMap($props['old'] ?? []);
                        })
                        ->columnSpanFull(),

                    // Langsung ATTRIBUTES
                    KeyValueEntry::make('properties_attributes')
                        ->label('Terbaru (attributes)')
                        ->state(function ($record) use ($normalizeProps, $toStringMap) {
                            $props = $normalizeProps($record->properties ?? null);
                            return $toStringMap($props['attributes'] ?? []);
                        })
                        ->columnSpanFull()
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('log_name')
            ->columns([
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable(),

                // Tampilkan nama model saja
                TextColumn::make('subject_type')
                    ->label('Subjek')
                    ->formatStateUsing(fn($state) => $state ? class_basename($state) : '-')
                    ->searchable(),

                // Pelaku (fallback "System")
                TextColumn::make('user.name')
                    ->label('Pelaku')
                    ->placeholder('System')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Pelaku')
                    ->searchable(),
                // Ringkasan perubahan dari JSON properties (old vs attributes)
                TagsColumn::make('perubahan')
                    ->label('Perubahan')
                    ->state(function ($record) {
                        $props = $record->properties;

                        // Normalisasi properties → array
                        if ($props instanceof Arrayable)
                            $props = $props->toArray();
                        if (is_string($props)) {
                            $decoded = json_decode($props, true);
                            if (json_last_error() === JSON_ERROR_NONE)
                                $props = $decoded;
                        }
                        if (!is_array($props))
                            return [];

                        $hidden = ['password', 'remember_token']; // field sensitif
                        $moneyFields = ['price', 'cost_price', 'total', 'amount', 'subtotal'];
                        $intFields = ['qty', 'reserved_qty', 'warehouse_id', 'product_variant_id', 'stock'];

                        $old = Arr::get($props, 'old', []);
                        $new = Arr::get($props, 'attributes', []);

                        $fmt = function (string $k, $v) use ($moneyFields, $intFields) {
                            if (is_null($v))
                                return 'null';
                            if (in_array($k, $moneyFields, true) && is_numeric($v)) {
                                return 'Rp ' . number_format((float) $v, 0, ',', '.');
                            }
                            if (in_array($k, $intFields, true) && is_numeric($v)) {
                                return number_format((float) $v, 0, ',', '.');
                            }
                            return (string) $v;
                        };

                        // Jika hanya attributes (mis. event created):
                        if (empty($old) && !empty($new)) {
                            $tags = collect($new)
                                ->reject(fn($v, $k) => in_array($k, $hidden, true))
                                ->map(fn($v, $k) => "{$k}: " . $fmt($k, $v))
                                ->values()
                                ->all();

                            // Jika semua field termasuk hidden → tampilkan indikator
                            if (empty($tags) && !empty($new)) {
                                $sens = implode(', ', array_keys($new));
                                return ["Field sensitif dibuat: {$sens}"];
                            }
                            return $tags;
                        }

                        // Ada old & attributes → diff
                        $keys = collect(array_keys($new + $old));
                        $visibleDiffs = [];
                        $hiddenChanged = [];

                        foreach ($keys as $k) {
                            $before = $old[$k] ?? null;
                            $after = $new[$k] ?? null;
                            if ($before === $after)
                                continue;

                            // Catat perubahan field tersembunyi
                            if (in_array($k, $hidden, true)) {
                                $hiddenChanged[] = $k;
                                continue;
                            }

                            // Delta angka (non-money)
                            $delta = null;
                            if (is_numeric($before) && is_numeric($after) && !in_array($k, $moneyFields, true)) {
                                $diff = (float) $after - (float) $before;
                                $sign = $diff > 0 ? '+' : '';
                                $delta = " ({$sign}" . number_format($diff, 0, ',', '.') . ")";
                            }

                            $visibleDiffs[] = "{$k}: '" . $fmt($k, $before) . "' → '" . $fmt($k, $after) . "'" . ($delta ?? '');
                        }

                        // Jika tidak ada perubahan yang terlihat karena semua yang berubah adalah field sensitif
                        if (empty($visibleDiffs) && !empty($hiddenChanged)) {
                            $list = implode(', ', $hiddenChanged);
                            return ["Field sensitif diubah: {$list}"]; // aman, tanpa nilai
                            // Jika ingin tampilkan masked:
                            // return collect($hiddenChanged)->map(fn($k) => "{$k}: '********' → '********'")->all();
                        }

                        return $visibleDiffs;
                    })
                    ->limit(5)
                    ->toggleable()
                    ->separator(','),
                TextColumn::make('created_at')->label('Waktu kejadian')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLogs::route('/'),
        ];
    }
}
