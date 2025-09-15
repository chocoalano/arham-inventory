<?php

namespace App\AppPanel\Clusters\FinanceReports\Resources\BallanceSheets;

use App\AppPanel\Clusters\FinanceReports\FinanceReportsCluster;
use App\AppPanel\Clusters\FinanceReports\Resources\BallanceSheets\Pages\ManageBallanceSheets;
use App\Models\Views\BalanceSheet;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;

class BallanceSheetResource extends Resource
{
    protected static ?string $model = BalanceSheet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $cluster = FinanceReportsCluster::class;

    protected static ?string $recordTitleAttribute = 'BalanceSheet';

    public static function getModelLabel(): string
    {
        return 'Neraca';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Neraca';
    }

    public static function table(Table $table): Table
    {
        $fmt = fn ($state) => number_format((float) $state, 2, ',', '.');

        return $table
            ->recordTitleAttribute('BalanceSheet')
            ->defaultSort('ends_on', 'desc')
            ->columns([
                TextColumn::make('fiscal_year')
                    ->label('FY')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('starts_on')
                    ->label('Periode Mulai')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('ends_on')
                    ->label('Periode Selesai')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('total_assets')
                    ->label('Assets')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->summarize([
                        Sum::make()
                            ->label('Total')
                            ->formatStateUsing($fmt),
                    ])
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('total_liabilities')
                    ->label('Liabilities')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->summarize([
                        Sum::make()
                            ->label('Total')
                            ->formatStateUsing($fmt),
                    ])
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('total_equity')
                    ->label('Equity')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->summarize([
                        Sum::make()
                            ->label('Total')
                            ->formatStateUsing($fmt),
                    ])
                    ->toggleable(),

                TextColumn::make('accounting_equation')
                    ->label('A - (L+E)')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->color(function ($state) {
                        $v = (float) $state;
                        // anggap “seimbang” jika |nilai| <= 0.0001
                        return abs($v) > 0.0001 ? 'danger' : 'success';
                    })
                    ->summarize([
                        Sum::make()
                            ->label('Total')
                            ->formatStateUsing($fmt),
                    ])
                    ->toggleable(),
            ])

            ->filters([
                // Filter by Fiscal Year (dinamis dari data)
                SelectFilter::make('fiscal_year')
                    ->label('FY')
                    ->options(fn () => BalanceSheet::query()
                        ->whereNotNull('fiscal_year')
                        ->distinct()
                        ->orderBy('fiscal_year', 'desc')
                        ->pluck('fiscal_year', 'fiscal_year')
                        ->toArray())
                    ->native(false),

                // Rentang tanggal periode
                Filter::make('periode')
                    ->label('Rentang Periode')
                    ->form([
                        DatePicker::make('starts_from')->label('Mulai dari'),
                        DatePicker::make('ends_until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['starts_from'] ?? null, fn ($q, $d) => $q->whereDate('starts_on', '>=', $d))
                            ->when($data['ends_until'] ?? null, fn ($q, $d) => $q->whereDate('ends_on', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['starts_from'])) {
                            $indicators[] = 'Mulai ≥ ' . $data['starts_from'];
                        }
                        if (! empty($data['ends_until'])) {
                            $indicators[] = 'Selesai ≤ ' . $data['ends_until'];
                        }
                        return $indicators;
                    })->columns(2),

                // Kisaran nilai total_assets
                Filter::make('assets_range')
                    ->label('Assets (Range)')
                    ->form([
                        TextInput::make('min')->numeric()->label('Min'),
                        TextInput::make('max')->numeric()->label('Max'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min'] ?? null, fn ($q, $v) => $q->where('total_assets', '>=', (float) $v))
                            ->when($data['max'] ?? null, fn ($q, $v) => $q->where('total_assets', '<=', (float) $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $out = [];
                        if ($data['min'] ?? null) $out[] = 'A ≥ ' . number_format((float) $data['min'], 2, ',', '.');
                        if ($data['max'] ?? null) $out[] = 'A ≤ ' . number_format((float) $data['max'], 2, ',', '.');
                        return $out;
                    })->columns(2),

                // Status seimbang (A - (L+E))
                TernaryFilter::make('balanced')
                    ->label('Seimbang? (A − (L+E) ≈ 0)')
                    ->queries(
                        true: fn (Builder $q) => $q->whereRaw('ABS(accounting_equation) <= 0.0001'),
                        false: fn (Builder $q) => $q->whereRaw('ABS(accounting_equation) > 0.0001'),
                        blank: fn (Builder $q) => $q
                    )
                    ->placeholder('Semua'),

                // Negatif saja (Equity/Liabilities/Assets)
                TernaryFilter::make('has_negative')
                    ->label('Ada Nilai Negatif?')
                    ->queries(
                        true: fn (Builder $q) => $q->where(fn ($qq) => $qq
                            ->where('total_assets', '<', 0)
                            ->orWhere('total_liabilities', '<', 0)
                            ->orWhere('total_equity', '<', 0)
                            ->orWhere('accounting_equation', '<', 0)),
                        false: fn (Builder $q) => $q->where('total_assets', '>=', 0)
                            ->where('total_liabilities', '>=', 0)
                            ->where('total_equity', '>=', 0)
                            ->where('accounting_equation', '>=', 0),
                        blank: fn (Builder $q) => $q
                    )
                    ->placeholder('Semua'),
            ], layout: FiltersLayout::AboveContent)

            // Quick search di beberapa kolom
            ->searchPlaceholder('Cari FY / tanggal / angka...')
            ->paginated([25, 50, 100])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBallanceSheets::route('/'),
        ];
    }
}
