<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialStocks;

use App\AppPanel\Clusters\Produksi\ProduksiCluster;
use App\AppPanel\Clusters\Produksi\Resources\RawMaterialStocks\Pages\ManageRawMaterialStocks;
use App\Models\RawMaterial\RawMaterialStock;
use BackedEnum;
use Filament\Actions\Action;
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

class RawMaterialStockResource extends Resource
{
    protected static ?string $model = RawMaterialStock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CubeTransparent;

    protected static ?string $cluster = ProduksiCluster::class;

    protected static ?string $recordTitleAttribute = 'RawMaterialStock';

    public static function getModelLabel(): string
    {
        return 'Stok Material';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Stok Material';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('raw_material_id')
                    ->label('Bahan Baku')
                    ->helperText('Pilih bahan baku yang digunakan. Jika belum ada, klik ikon ➕ untuk menambahkan bahan baku baru.')
                    ->relationship('rawMaterial', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->prefixAction(
                        fn () => Action::make('createRawMaterial')
                            ->icon('heroicon-o-plus')
                            ->tooltip('Tambah Bahan Baku Baru')
                            ->url(route('filament.app.produksi.resources.raw-materials.index'))
                            ->openUrlInNewTab()
                    ),

                Select::make('warehouse_id')
                    ->label('Gudang')
                    ->helperText('Pilih gudang tempat bahan baku disimpan. Jika belum ada, klik ikon ➕ untuk menambahkan gudang baru.')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->prefixAction(
                        fn () => Action::make('createWarehouse')
                            ->icon('heroicon-o-plus')
                            ->tooltip('Tambah Gudang Baru')
                            ->url(route('filament.app.inventory.resources.warehouses.index'))
                            ->openUrlInNewTab()
                    ),

                Select::make('batch_id')
                    ->label('Batch (Opsional)')
                    ->helperText('Pilih batch/lot bahan baku jika tersedia. Klik ikon ➕ untuk menambahkan batch baru.')
                    ->relationship('batch', 'lot_no')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->prefixAction(
                        fn () => Action::make('createBatch')
                            ->icon('heroicon-o-plus')
                            ->tooltip('Tambah Batch Baru')
                            ->url(route('filament.app.produksi.resources.raw-material-batches.index'))
                            ->openUrlInNewTab()
                    ),

                Select::make('unit_id')
                    ->label('Satuan (UoM)')
                    ->helperText('Pilih satuan bahan baku, misalnya KG, Gram, Liter. Klik ikon ➕ untuk menambahkan satuan baru.')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->prefixAction(
                        fn () => Action::make('createUnit')
                            ->icon('heroicon-o-plus')
                            ->tooltip('Tambah Satuan Baru')
                            ->url(route('filament.app.produksi.resources.units.index'))
                            ->openUrlInNewTab()
                    ),

                TextInput::make('quantity')
                    ->label('Jumlah')
                    ->helperText('Masukkan jumlah stok bahan baku yang disimpan di gudang ini.')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('rawMaterial.name'),
                TextEntry::make('warehouse.name'),
                TextEntry::make('batch.lot_no'),
                TextEntry::make('unit.name'),
                TextEntry::make('quantity')
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
            ->recordTitleAttribute('RawMaterialStock')
            ->columns([
                TextColumn::make('rawMaterial.name')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->searchable(),
                TextColumn::make('batch.lot_no')
                    ->searchable(),
                TextColumn::make('unit.name')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
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
            'index' => ManageRawMaterialStocks::route('/'),
        ];
    }
}
