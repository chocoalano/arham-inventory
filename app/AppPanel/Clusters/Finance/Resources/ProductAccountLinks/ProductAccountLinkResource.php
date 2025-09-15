<?php

namespace App\AppPanel\Clusters\Finance\Resources\ProductAccountLinks;

use App\AppPanel\Clusters\Finance\FinanceCluster;
use App\AppPanel\Clusters\Finance\Resources\ProductAccountLinks\Pages\ManageProductAccountLinks;
use App\Models\Finance\ProductAccountLink;
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

class ProductAccountLinkResource extends Resource
{
    protected static ?string $model = ProductAccountLink::class;

    protected static string|BackedEnum|null $navigationIcon = "eos-product-classes";

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $recordTitleAttribute = 'ProductAccountLink';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')->searchable()->required(),
                Select::make('inventory_account_id')
                    ->relationship('inventoryAccount', 'name')->searchable()->label('Inventory Account'),
                Select::make('cogs_account_id')
                    ->relationship('cogsAccount', 'name')->searchable()->label('COGS Account'),
                Select::make('sales_account_id')
                    ->relationship('salesAccount', 'name')->searchable()->label('Sales Account'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('product_id')
                    ->numeric(),
                TextEntry::make('inventory_account_id')
                    ->numeric(),
                TextEntry::make('cogs_account_id')
                    ->numeric(),
                TextEntry::make('sales_account_id')
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
            ->recordTitleAttribute('ProductAccountLink')
            ->columns([
                TextColumn::make('product.name')->label('Produk')->searchable(),
                TextColumn::make('inventoryAccount.number')->label('Inv No')->toggleable(),
                TextColumn::make('inventoryAccount.name')->label('Inventory')->wrap(),
                TextColumn::make('cogsAccount.name')->label('COGS')->wrap(),
                TextColumn::make('salesAccount.name')->label('Sales')->wrap(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageProductAccountLinks::route('/'),
        ];
    }
}
