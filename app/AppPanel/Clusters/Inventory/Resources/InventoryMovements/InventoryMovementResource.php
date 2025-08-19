<?php

namespace App\AppPanel\Clusters\Inventory\Resources\InventoryMovements;

use App\AppPanel\Clusters\Inventory\InventoryCluster;
use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Pages\ManageInventoryMovements;
use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Widgets\InventoryStats;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\Warehouse;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = InventoryCluster::class;

    protected static ?string $recordTitleAttribute = 'InventoryMovement';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Movement (Read-only)')->schema([
                    Select::make('source_warehouse_id')->label('From Warehouse')->options(
                        fn() => Warehouse::orderBy('created_at')->pluck('name', 'id')
                    ),
                    Select::make('destination_warehouse_id')->label('To Warehouse')->options(
                        fn() => Warehouse::orderBy('created_at')->pluck('name', 'id')
                    ),
                    Select::make('product_variant_id')->label('Varian')->options(
                        fn() => ProductVariant::orderBy('sku_variant')->pluck('sku_variant', 'id')
                    ),
                    TextInput::make('qty_change')->label('Perubahan Qty'),
                    TextInput::make('type')->label('Tipe')->default('pemindahan')->disabled(),
                    DateTimePicker::make('occurred_at')->label('Waktu')->default(now()),
                    TextInput::make('remarks')->label('Catatan'),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('InventoryMovement')
            ->columns([
                TextColumn::make('transaction.reference_number')->searchable(),
                TextColumn::make('from_warehouse.name')->searchable(),
                TextColumn::make('to_warehouse.name')->searchable(),
                TextColumn::make('variant.sku_variant')->searchable(),
                TextColumn::make('variant.sku_variant')->searchable(),
                TextColumn::make('type')->label('Jenis')->searchable(),
                TextColumn::make('qty_change')->label('qty')->searchable(),
                TextColumn::make('occurred_at')->searchable(),
                TextColumn::make('remarks')->searchable(),
                TextColumn::make('creator.name')->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('cetak_resi')
                    ->label('Cetak resi')
                    ->url(fn(): string => route('inventory.cetak-resi'))
                    ->openUrlInNewTab()
                    ->visible(fn(): bool => auth()->user()->hasPermissionTo('viewAny-invoice')),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            InventoryStats::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryMovements::route('/'),
        ];
    }
}
