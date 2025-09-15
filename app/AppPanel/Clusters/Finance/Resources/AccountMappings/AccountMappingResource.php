<?php

namespace App\AppPanel\Clusters\Finance\Resources\AccountMappings;

use App\AppPanel\Clusters\Finance\FinanceCluster;
use App\AppPanel\Clusters\Finance\Resources\AccountMappings\Pages\ManageAccountMappings;
use App\Models\Finance\AccountMapping;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountMappingResource extends Resource
{
    protected static ?string $model = AccountMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Map;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $recordTitleAttribute = 'AccountMapping';

    /** Label singular/plural di UI */
    public static function getModelLabel(): string
    {
        return 'Account Mapping';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pemetaan Akun';
    }

    /** Atribut yang masuk global search (panel search bar) */
    public static function getGloballySearchableAttributes(): array
    {
        return ['key', 'account.name', 'account.number'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')->required()->maxLength(64)->unique(ignoreRecord: true)
                    ->helperText('Contoh: sales_revenue, cogs, inventory, ar, ap, tax_output, tax_input, shipping_income'),
                Select::make('account_id')->relationship('account', 'name')->preload()->searchable()->required()
                    ->helperText('Pilih account yang sebelumnya kamu buat.'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('key'),
                TextEntry::make('account_id')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('AccountMapping')
            ->columns([
                TextColumn::make('key')->searchable(),
                TextColumn::make('account.number')->label('No Akun'),
                TextColumn::make('account.name')->label('Nama Akun')->searchable(),
                TextColumn::make('updated_at')->since()->label('Update'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ManageAccountMappings::route('/'),
        ];
    }
}
