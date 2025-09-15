<?php

namespace App\AppPanel\Clusters\Finance\Resources\FiscalYears;

use App\AppPanel\Clusters\Finance\FinanceCluster;
use App\AppPanel\Clusters\Finance\Resources\FiscalYears\Pages\ManageFiscalYears;
use App\AppPanel\Clusters\Finance\Resources\FiscalYears\RelationManagers\PeriodsRelationManager;
use App\Models\Finance\FiscalYear;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class FiscalYearResource extends Resource
{
    protected static ?string $model = FiscalYear::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDateRange;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $recordTitleAttribute = 'FiscalYear';

    /** Label singular/plural di UI */
    public static function getModelLabel(): string
    {
        return 'Tahun Fiskal';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tahun Fiskal';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('year')
                    ->label('Tahun')
                    ->required()
                    ->numeric()
                    ->helperText('Masukkan tahun periode dalam format empat digit. Contoh: 2024.')
                    ->suffixAction(
                        Action::make('generate')
                            ->label('Generate')
                            ->icon('heroicon-o-arrow-path-rounded-square')
                            ->tooltip('Buat tahun berdasarkan tahun saat ini.')
                            // Aksi ini akan mengisi field 'year' dengan timestamp
                            ->action(fn($set) => $set('year', now()->format('Y')))
                    ),
                DatePicker::make('starts_on')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->default(fn (): string => Carbon::now()->startOfYear()->toDateString())
                    ->helperText('Pilih tanggal dimulainya periode akuntansi. Contoh: 1 Januari 2024.'),
                DatePicker::make('ends_on')
                    ->label('Tanggal Berakhir')
                    ->required()
                    ->default(fn (): string => Carbon::now()->endOfYear()->toDateString())
                    ->helperText('Pilih tanggal berakhirnya periode akuntansi. Contoh: 31 Desember 2024.'),
                Toggle::make('is_closed')
                    ->label('Periode Ditutup')
                    ->required()
                    ->helperText('Aktifkan jika periode ini sudah final dan tidak ada lagi transaksi yang akan dicatat.'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('year')
                    ->numeric(),
                TextEntry::make('starts_on')
                    ->date(),
                TextEntry::make('ends_on')
                    ->date(),
                IconEntry::make('is_closed')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('FiscalYear')
            ->columns([
                TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('starts_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_on')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_closed')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PeriodsRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => ManageFiscalYears::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
