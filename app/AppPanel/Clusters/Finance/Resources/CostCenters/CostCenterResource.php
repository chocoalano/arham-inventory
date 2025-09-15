<?php

namespace App\AppPanel\Clusters\Finance\Resources\CostCenters;

use App\AppPanel\Clusters\Finance\FinanceCluster;
use App\AppPanel\Clusters\Finance\Resources\CostCenters\Pages\ManageCostCenters;
use App\Models\Finance\CostCenter;
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

class CostCenterResource extends Resource
{
    protected static ?string $model = CostCenter::class;

    protected static string|BackedEnum|null $navigationIcon = "grommet-samsung-pay";

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $recordTitleAttribute = 'CostCenter';

    /** Label singular/plural di UI */
    public static function getModelLabel(): string
    {
        return 'Pusat Biaya';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pusat Biaya';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode Cost Center')
                    ->required()
                    ->helperText('Kode unik untuk membedakan setiap cost center. Biasanya berupa singkatan/divisi, contoh: HR01, MKT02, PRD03.')
                    ->suffixAction(
                        Action::make('generate')
                            ->label('Generate')
                            ->icon('heroicon-o-arrow-path-rounded-square')
                            ->tooltip('Buat nomor acak berbasis waktu')
                            // Aksi ini akan mengisi field 'code' dengan timestamp
                            ->action(fn($set) => $set('code', now()->timestamp))
                    ),
                TextInput::make('name')->label('Nama Cost Center')->required()->helperText('Nama atau deskripsi cost center. Contoh: Human Resource, Marketing, Produksi.'),
                Toggle::make('is_active')->label('Aktif')->required()->helperText('Tentukan apakah cost center ini masih aktif digunakan atau tidak. Nonaktifkan bila sudah tidak dipakai.'),

            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('name'),
                IconEntry::make('is_active')
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
            ->recordTitleAttribute('CostCenter')
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                IconColumn::make('is_active')
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
            'index' => ManageCostCenters::route('/'),
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
