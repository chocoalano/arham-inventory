<?php

namespace App\AppPanel\Clusters\FinanceReports\Resources\ProfitAndLosses;

use App\AppPanel\Clusters\FinanceReports\FinanceReportsCluster;
use App\AppPanel\Clusters\FinanceReports\Resources\ProfitAndLosses\Pages\ManageProfitAndLosses;
use App\Models\Views\ProfitAndLoss;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class ProfitAndLossesResource extends Resource
{
    protected static ?string $model = ProfitAndLoss::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsUpDown;

    protected static ?string $cluster = FinanceReportsCluster::class;

    protected static ?string $recordTitleAttribute = 'ProfitAndLoss';

    /** Label tunggal */
    public static function getModelLabel(): string
    {
        return 'Laba Rugi';
    }

    /** Label jamak */
    public static function getPluralModelLabel(): string
    {
        return 'Laporan Laba Rugi';
    }

    public static function table(Table $table): Table
    {
        // Format angka agar seragam (contoh: 1.000,00)
        $fmt = fn ($state) => number_format((float) $state, 2, ',', '.');

        return $table
            ->recordTitleAttribute('ProfitAndLoss')
            ->defaultSort('ends_on', 'desc') // urutkan berdasarkan periode selesai terbaru
            ->columns([
                TextColumn::make('fiscal_year')
                    ->label('Tahun Fiskal')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('starts_on')
                    ->label('Periode Mulai')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('ends_on')
                    ->label('Periode Selesai')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('total_revenue')
                    ->label('Pendapatan')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->summarize([
                        Sum::make()->label('Total')->formatStateUsing($fmt),
                    ]),

                TextColumn::make('total_expense')
                    ->label('Beban')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->summarize([
                        Sum::make()->label('Total')->formatStateUsing($fmt),
                    ]),

                TextColumn::make('net_profit')
                    ->label('Laba (Rugi) Bersih')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->summarize([
                        Sum::make()->label('Total')->formatStateUsing($fmt),
                    ]),
            ])

            ->filters([
                // Filter berdasarkan Tahun Fiskal
                SelectFilter::make('fiscal_year')
                    ->label('Tahun Fiskal')
                    ->options(fn () => ProfitAndLoss::query()
                        ->whereNotNull('fiscal_year')
                        ->distinct()
                        ->orderBy('fiscal_year', 'desc')
                        ->pluck('fiscal_year', 'fiscal_year')
                        ->toArray())
                    ->native(false),

                // Filter berdasarkan rentang periode
                Filter::make('periode')
                    ->label('Rentang Periode')
                    ->form([
                        DatePicker::make('mulai_dari')->label('Mulai dari'),
                        DatePicker::make('sampai')->label('Sampai dengan'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['mulai_dari'] ?? null, fn ($q, $d) => $q->whereDate('starts_on', '>=', $d))
                            ->when($data['sampai'] ?? null, fn ($q, $d) => $q->whereDate('ends_on', '<=', $d));
                    })->columns(2)->columnSpan(2),

                // Filter berdasarkan kisaran Pendapatan
                Filter::make('revenue_range')
                    ->label('Pendapatan (Range)')
                    ->form([
                        TextInput::make('min')->numeric()->label('Minimal'),
                        TextInput::make('max')->numeric()->label('Maksimal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min'] ?? null, fn ($q, $v) => $q->where('total_revenue', '>=', (float) $v))
                            ->when($data['max'] ?? null, fn ($q, $v) => $q->where('total_revenue', '<=', (float) $v));
                    })->columns(2)->columnSpan(2),

                // Filter berdasarkan status (untung / rugi)
                TernaryFilter::make('status')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Profit')
                    ->falseLabel('Rugi')
                    ->queries(
                        true: fn (Builder $q) => $q->where('net_profit', '>=', 0),
                        false: fn (Builder $q) => $q->where('net_profit', '<', 0),
                        blank: fn (Builder $q) => $q
                    ),
            ], layout: FiltersLayout::AboveContent)

            ->searchPlaceholder('Cari tahun, periode, atau nilai…')
            ->paginated([25, 50, 100])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([]),
                // Bisa ditambahkan tombol export jika diperlukan
                // \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProfitAndLosses::route('/'),
        ];
    }
}
