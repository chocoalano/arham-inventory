<?php

namespace App\AppPanel\Clusters\FinanceReports\Resources\GeneralLedgers;

use App\AppPanel\Clusters\FinanceReports\FinanceReportsCluster;
use App\AppPanel\Clusters\FinanceReports\Resources\GeneralLedgers\Pages\ManageGeneralLedgers;
use App\Models\Views\GeneralLedger;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;

class GeneralLedgerResource extends Resource
{
    protected static ?string $model = GeneralLedger::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBarSquare;

    protected static ?string $cluster = FinanceReportsCluster::class;

    protected static ?string $recordTitleAttribute = 'GeneralLedger';

    /** Label singular/plural di UI */
    public static function getModelLabel(): string
    {
        return 'Buku Besar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Buku Besar';
    }

    public static function table(Table $table): Table
    {
        $fmt = fn ($state) => number_format((float) $state, 2, ',', '.');

        return $table
            ->recordTitleAttribute('GeneralLedger')
            ->defaultSort('journal_date', 'desc')
            ->columns([
                TextColumn::make('journal_no')
                    ->label('No')
                    ->searchable(),

                TextColumn::make('journal_date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('account_number')
                    ->label('Akun')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('account_name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('description')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('debit')
                    ->numeric(2)
                    ->alignRight()
                    ->label('Debit')
                    ->formatStateUsing($fmt)
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->summarize([
                        Sum::make()->label('Total')->formatStateUsing($fmt),
                    ]),

                TextColumn::make('credit')
                    ->numeric(2)
                    ->alignRight()
                    ->label('Credit')
                    ->formatStateUsing($fmt)
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->summarize([
                        Sum::make()->label('Total')->formatStateUsing($fmt),
                    ]),

                TextColumn::make('dc')
                    ->numeric(2)
                    ->alignRight()
                    ->label('D - C')
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->formatStateUsing($fmt)
                    ->summarize([
                        Sum::make()->label('Total')->formatStateUsing($fmt),
                    ]),

                TextColumn::make('source_type')
                    ->toggleable(),

                TextColumn::make('source_id')
                    ->toggleable(),
            ])

            ->filters([
                // Rentang tanggal (journal_date)
                Filter::make('periode')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('d_from')->label('Dari'),
                        DatePicker::make('d_to')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['d_from'] ?? null, fn ($q, $v) => $q->whereDate('journal_date', '>=', $v))
                            ->when($data['d_to'] ?? null, fn ($q, $v) => $q->whereDate('journal_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $i = [];
                        if (!empty($data['d_from'])) $i[] = '≥ ' . $data['d_from'];
                        if (!empty($data['d_to']))   $i[] = '≤ ' . $data['d_to'];
                        return $i;
                    })->columns(2)->columnSpan(2),

                // Filter akun (gabung account_number + account_name)
                SelectFilter::make('account_number')
                    ->label('Akun')
                    ->options(fn () => GeneralLedger::query()
                        ->selectRaw("account_number, CONCAT(account_number,' — ',account_name) as label")
                        ->distinct()
                        ->orderBy('account_number')
                        ->pluck('label', 'account_number')
                        ->toArray())
                    ->searchable()
                    ->native(false),

                // Sumber transaksi
                SelectFilter::make('source_type')
                    ->label('Sumber')
                    ->options(fn () => GeneralLedger::query()
                        ->whereNotNull('source_type')
                        ->distinct()
                        ->orderBy('source_type')
                        ->pluck('source_type', 'source_type')
                        ->toArray())
                    ->native(false),

                // Kisaran nominal
                Filter::make('amount_range')
                    ->label('Nominal (Debit/Credit)')
                    ->form([
                        TextInput::make('min')->label('Min')->numeric(),
                        TextInput::make('max')->label('Max')->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        $min = isset($data['min']) && $data['min'] !== '' ? (float)$data['min'] : null;
                        $max = isset($data['max']) && $data['max'] !== '' ? (float)$data['max'] : null;
                        return $query->when($min, fn ($q) => $q->where(function ($qq) use ($min) {
                                $qq->where('debit', '>=', $min)->orWhere('credit', '>=', $min);
                            }))
                            ->when($max, fn ($q) => $q->where(function ($qq) use ($max) {
                                $qq->where('debit', '<=', $max)->orWhere('credit', '<=', $max);
                            }));
                    })
                    ->indicateUsing(function (array $data): array {
                        $out = [];
                        if ($data['min'] ?? null) $out[] = '≥ ' . number_format((float)$data['min'], 2, ',', '.');
                        if ($data['max'] ?? null) $out[] = '≤ ' . number_format((float)$data['max'], 2, ',', '.');
                        return $out;
                    })->columns(2),
            ], layout: FiltersLayout::AboveContent)

            ->searchPlaceholder('Cari no jurnal / akun / deskripsi…')
            ->paginated([25, 50, 100])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([]),
                // (Opsional) export CSV/XLSX bila Anda pakai plugin export
                // \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGeneralLedgers::route('/'),
        ];
    }
}
