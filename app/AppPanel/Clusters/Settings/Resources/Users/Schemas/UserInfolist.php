<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
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
                Section::make('Detail data')
                    ->inlineLabel()
                    ->components([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->label('Email address'),
                        TextEntry::make('email_verified_at')
                            ->dateTime(),
                        TextEntry::make('warehouse.name')
                            ->label('Area penempatan kerja'),
                        TextEntry::make('roles.name')
                            ->color('primary')
                            ->label('Peran pengguna'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->columnSpanFull(),
                Section::make('Aktifitas Terakhir')
                    ->inlineLabel()
                    ->components([
                        TextEntry::make('properties_debug')
                            ->label('Debug Properties (raw)')
                            ->state(function ($record) use ($normalizeProps) {
                                $activity = $record->log()->latest()->first();

                                if (!$activity) {
                                    return '(kosong)';
                                }

                                $arr = $normalizeProps($activity->properties ?? null);
                                return $arr === []
                                    ? '(kosong)'
                                    : json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            })
                            ->copyable()
                            ->prose()
                            ->columnSpanFull(),

                        // OLD dari activity terbaru
                        KeyValueEntry::make('properties_old')
                            ->label('Sebelumnya (old)')
                            ->state(function ($record) use ($normalizeProps, $toStringMap) {
                                $activity = $record->log()->latest()->first();
                                $props = $normalizeProps($activity?->properties ?? null);
                                return $toStringMap($props['old'] ?? []);
                            })
                            ->columnSpanFull(),

                        // ATTRIBUTES dari activity terbaru
                        KeyValueEntry::make('properties_attributes')
                            ->label('Terbaru (attributes)')
                            ->state(function ($record) use ($normalizeProps, $toStringMap) {
                                $activity = $record->log()->latest()->first();
                                $props = $normalizeProps($activity?->properties ?? null);
                                return $toStringMap($props['attributes'] ?? []);
                            })
                            ->columnSpanFull(),
                    ])->columnSpanFull()
            ]);
    }
}
