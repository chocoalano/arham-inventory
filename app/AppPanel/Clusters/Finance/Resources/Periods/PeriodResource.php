<?php

namespace App\AppPanel\Clusters\Finance\Resources\Periods;

use App\AppPanel\Clusters\Finance\FinanceCluster;
use App\AppPanel\Clusters\Finance\Resources\Periods\Pages\ManagePeriods;
use App\Models\Finance\Period;
use BackedEnum;
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
use Filament\Forms\Components\Select;
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

class PeriodResource extends Resource
{
    protected static ?string $model = Period::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $recordTitleAttribute = 'Period';

    /** Label singular/plural di UI */
    public static function getModelLabel(): string
    {
        return 'Periode';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Periode';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('fiscal_year_id')
                    ->relationship('fiscalYear', 'year')->required()->searchable(),
                TextInput::make('period_no')->numeric()->minValue(1)->maxValue(13)->required(),
                DatePicker::make('starts_on')->required(),
                DatePicker::make('ends_on')->required(),
                Toggle::make('is_closed')->label('Ditutup?'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('fiscalYear.year')
                    ->numeric(),
                TextEntry::make('period_no')
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
            ->recordTitleAttribute('Period')
            ->columns([
                TextColumn::make('fiscalYear.year')->label('FY')->sortable(),
                TextColumn::make('period_no')
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

    public static function getPages(): array
    {
        return [
            'index' => ManagePeriods::route('/'),
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
