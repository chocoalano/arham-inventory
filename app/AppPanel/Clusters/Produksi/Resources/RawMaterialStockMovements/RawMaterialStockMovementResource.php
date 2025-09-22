<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialStockMovements;

use App\AppPanel\Clusters\Produksi\ProduksiCluster;
use App\AppPanel\Clusters\Produksi\Resources\RawMaterialStockMovements\Pages\ManageRawMaterialStockMovements;
use App\Models\RawMaterial\RawMaterialStockMovement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RawMaterialStockMovementResource extends Resource
{
    protected static ?string $model = RawMaterialStockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cube;

    protected static ?string $cluster = ProduksiCluster::class;

    protected static ?string $recordTitleAttribute = 'RawMaterialStockMovement';
    public static function getModelLabel(): string
    {
        return 'Perpindahan Stok';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Perpindahan Stok';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Select::make('raw_material_id')
                        ->label('Bahan Baku')
                        ->helperText('Pilih bahan baku yang stoknya akan bergerak. Klik ikon ➕ untuk menambahkan bahan baku baru.')
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
                        ->helperText('Pilih gudang tempat bahan baku disimpan. Klik ikon ➕ untuk menambahkan gudang baru.')
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
                        ->helperText('Pilih batch atau lot bahan baku jika ada. Jika tidak, biarkan kosong.')
                        ->relationship('batch', 'lot_no')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->prefixAction(
                            fn () => Action::make('createBatch')
                                ->icon('heroicon-o-plus')
                                ->tooltip('Tambah Batch Bahan Baku Baru')
                                ->url(route('filament.app.produksi.resources.raw-material-batches.index'))
                                ->openUrlInNewTab()
                        ),

                    Select::make('unit_id')
                        ->label('Satuan (UoM)')
                        ->helperText('Pilih satuan bahan baku, misalnya KG, Gram, Liter.')
                        ->relationship('unit', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('type')
                        ->label('Jenis Pergerakan')
                        ->helperText('Tentukan jenis pergerakan stok: IN = masuk, OUT = keluar, ADJUST = penyesuaian, TRANSFER = perpindahan antar gudang.')
                        ->options([
                            'in' => 'Masuk',
                            'out' => 'Keluar',
                            'adjust' => 'Penyesuaian',
                            'transfer' => 'Transfer',
                        ])
                        ->required(),

                    TextInput::make('qty')
                        ->label('Jumlah')
                        ->helperText('Masukkan jumlah pergerakan stok. Angka positif untuk masuk, negatif untuk keluar (jika tidak otomatis).')
                        ->required()
                        ->numeric(),

                    TextInput::make('unit_cost')
                        ->label('Harga Satuan (Opsional)')
                        ->helperText('Masukkan harga per satuan bahan baku, digunakan untuk pencatatan biaya rata-rata (average cost).')
                        ->numeric()
                        ->nullable(),

                    TextInput::make('reference_type')
                        ->label('Tipe Referensi')
                        ->helperText('Tentukan tipe referensi transaksi, misalnya PO (Purchase Order), MO (Manufacturing Order), atau lainnya.')
                        ->required(),

                    TextInput::make('reference_id')
                        ->label('ID Referensi')
                        ->helperText('Masukkan ID dari referensi transaksi, misalnya nomor PO atau nomor MO.')
                        ->required()
                        ->numeric(),

                    TextInput::make('note')
                        ->label('Catatan')
                        ->helperText('Tambahkan catatan tambahan terkait pergerakan stok ini.')
                        ->nullable(),

                    DateTimePicker::make('moved_at')
                        ->label('Waktu Pergerakan')
                        ->helperText('Masukkan tanggal dan waktu kapan stok bergerak.')
                        ->required(),
                ])
                ->Columns(3)
                ->ColumnSpanFull()
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('rawMaterial.name'),
                TextEntry::make('warehouse.name'),
                TextEntry::make('batch.id'),
                TextEntry::make('unit.name'),
                TextEntry::make('type'),
                TextEntry::make('qty')
                    ->numeric(),
                TextEntry::make('unit_cost')
                    ->numeric(),
                TextEntry::make('reference_type'),
                TextEntry::make('reference_id')
                    ->numeric(),
                TextEntry::make('note'),
                TextEntry::make('moved_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('RawMaterialStockMovement')
            ->columns([
                TextColumn::make('rawMaterial.name')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->searchable(),
                TextColumn::make('batch.id')
                    ->searchable(),
                TextColumn::make('unit.name')
                    ->searchable(),
                TextColumn::make('type'),
                TextColumn::make('qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reference_type')
                    ->searchable(),
                TextColumn::make('reference_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('note')
                    ->searchable(),
                TextColumn::make('moved_at')
                    ->dateTime()
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
            'index' => ManageRawMaterialStockMovements::route('/'),
        ];
    }
}
