<?php

namespace App\AppPanel\Clusters\FinanceReports\Resources\TrialBallances;

use App\AppPanel\Clusters\FinanceReports\FinanceReportsCluster;
use App\AppPanel\Clusters\FinanceReports\Resources\TrialBallances\Pages\ManageTrialBallances;
use App\Models\Views\TrialBalance;
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

class TrialBallanceResource extends Resource
{
    protected static ?string $model = TrialBalance::class;

    protected static string|BackedEnum|null $navigationIcon = "codicon-law";

    protected static ?string $cluster = FinanceReportsCluster::class;

    protected static ?string $recordTitleAttribute = 'TrialBalance';

    public static function getModelLabel(): string
    {
        return 'Neraca Percobaan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Neraca Percobaan';
    }

    public static function table(Table $table): Table
    {
        $fmt = fn ($state) => number_format((float) $state, 2, ',', '.');

        return $table
            ->recordTitleAttribute('TrialBalance')
            ->defaultSort('ends_on', 'desc')
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

                TextColumn::make('account_number')
                    ->label('Nomor Akun')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('account_name')
                    ->label('Nama Akun')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('total_debit')
                    ->label('Debet')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->color(fn ($state) => (float) $state < 0
                        ? 'danger'
                        : ((float) $state > 0 ? 'success' : 'secondary'))
                    ->summarize([
                        Sum::make()->label('Total')->formatStateUsing($fmt),
                    ]),

                TextColumn::make('total_credit')
                    ->label('Kredit')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->color(fn ($state) => (float) $state < 0
                        ? 'danger'
                        : ((float) $state > 0 ? 'success' : 'secondary'))
                    ->summarize([
                        Sum::make()->label('Total')->formatStateUsing($fmt),
                    ]),

                TextColumn::make('balance')
                    ->label('Saldo (D − K)')
                    ->alignRight()
                    ->numeric(2)
                    ->formatStateUsing($fmt)
                    ->color(fn ($state) => (float) $state < 0
                        ? 'danger'
                        : ((float) $state > 0 ? 'success' : 'secondary'))
                    ->summarize([
                        Sum::make()->label('Total')->formatStateUsing($fmt),
                    ]),
            ])

            ->filters([
                SelectFilter::make('fiscal_year')
                    ->label('Tahun Fiskal')
                    ->options(fn () => TrialBalance::query()
                        ->whereNotNull('fiscal_year')
                        ->distinct()
                        ->orderBy('fiscal_year', 'desc')
                        ->pluck('fiscal_year', 'fiscal_year')
                        ->toArray())
                    ->native(false),

                SelectFilter::make('account_number')
                    ->label('Nomor Akun')
                    ->options(fn () => TrialBalance::query()
                        ->whereNotNull('account_number')
                        ->distinct()
                        ->orderBy('account_number')
                        ->pluck('account_number', 'account_number')
                        ->toArray())
                    ->searchable()
                    ->native(false),

                SelectFilter::make('account_name')
                    ->label('Nama Akun')
                    ->options(fn () => TrialBalance::query()
                        ->whereNotNull('account_name')
                        ->distinct()
                        ->orderBy('account_name')
                        ->pluck('account_name', 'account_name')
                        ->toArray())
                    ->searchable()
                    ->native(false),

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

                Filter::make('nominal_range')
                    ->label('Nominal (Debet/Kredit)')
                    ->form([
                        TextInput::make('min')->label('Minimal')->numeric(),
                        TextInput::make('max')->label('Maksimal')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $min = isset($data['min']) && $data['min'] !== '' ? (float) $data['min'] : null;
                        $max = isset($data['max']) && $data['max'] !== '' ? (float) $data['max'] : null;

                        return $query
                            ->when($min, fn ($q) => $q->where(function ($qq) use ($min) {
                                $qq->where('total_debit', '>=', $min)
                                   ->orWhere('total_credit', '>=', $min);
                            }))
                            ->when($max, fn ($q) => $q->where(function ($qq) use ($max) {
                                $qq->where('total_debit', '<=', $max)
                                   ->orWhere('total_credit', '<=', $max);
                            }));
                    })->columns(2)->columnSpan(2),

                TernaryFilter::make('balanced')
                    ->label('Seimbang? (|D − K| ≈ 0)')
                    ->placeholder('Semua')
                    ->queries(
                        true: fn (Builder $q) => $q->whereRaw('ABS(balance) <= 0.0001'),
                        false: fn (Builder $q) => $q->whereRaw('ABS(balance) > 0.0001'),
                        blank: fn (Builder $q) => $q
                    ),
            ], layout: FiltersLayout::AboveContent)

            ->searchPlaceholder('Cari tahun, akun, atau periode…')
            ->paginated([25, 50, 100])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTrialBallances::route('/'),
        ];
    }
}
