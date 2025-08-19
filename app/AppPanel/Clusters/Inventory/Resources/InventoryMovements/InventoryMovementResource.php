<?php

namespace App\AppPanel\Clusters\Inventory\Resources\InventoryMovements;

use App\AppPanel\Clusters\Inventory\InventoryCluster;
use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Pages\ManageInventoryMovements;
use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Widgets\InventoryStats;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\Invoice;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Transaction;
use App\Models\Inventory\Warehouse;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Truck;

    protected static ?string $cluster = InventoryCluster::class;

    protected static ?string $recordTitleAttribute = 'inventoryMovement';
    protected static ?string $modelLabel = 'Perpindahan Stok Barang';
    protected static ?string $navigationLabel = 'Perpindahan Stok Barang';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Mengubah label section menjadi Bahasa Indonesia
                Section::make('Perpindahan Stok Barang')->schema([
                    // Mengubah label 'From Warehouse' menjadi 'Dari Gudang'
                    Select::make('source_warehouse_id')->label('Dari Gudang')->options(
                        fn() => Warehouse::orderBy('created_at')->pluck('name', 'id')
                    ),
                    // Mengubah label 'To Warehouse' menjadi 'Ke Gudang'
                    Select::make('destination_warehouse_id')->label('Ke Gudang')->options(
                        fn() => Warehouse::orderBy('created_at')->pluck('name', 'id')
                    ),
                    // Label 'Varian' sudah benar
                    Select::make('product_variant_id')->label('Varian')->options(
                        fn() => ProductVariant::orderBy('sku_variant')->pluck('sku_variant', 'id')
                    ),
                    // Mengubah label 'Perubahan Qty' menjadi 'Jumlah Perubahan'
                    TextInput::make('qty_change')->label('Jumlah Perubahan'),
                    // Label 'Tipe' sudah benar
                    TextInput::make('type')->label('Tipe')->default('pemindahan')->disabled(),
                    // Label 'Waktu' sudah benar
                    DateTimePicker::make('occurred_at')->label('Waktu')->default(now()),
                    // Label 'Catatan' sudah benar
                    TextInput::make('remarks')->label('Catatan'),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('InventoryMovement')
            ->columns([
                // Menambahkan label 'Nomor Referensi'
                TextColumn::make('transaction.reference_number')->label('Nomor Referensi')->searchable(),
                // Menambahkan label 'Dari Gudang'
                TextColumn::make('from_warehouse.name')->label('Dari Gudang')->searchable(),
                // Menambahkan label 'Ke Gudang'
                TextColumn::make('to_warehouse.name')->label('Ke Gudang')->searchable(),
                // Menambahkan label 'Varian Produk'
                TextColumn::make('variant.sku_variant')->label('Varian Produk')->searchable(),
                // Label 'Jenis' sudah benar
                TextColumn::make('type')->label('Jenis')->searchable(),
                // Mengubah label 'qty' menjadi 'Jumlah'
                TextColumn::make('qty_change')->label('Jumlah')->searchable(),
                // Menambahkan label 'Waktu'
                TextColumn::make('occurred_at')->label('Waktu')->searchable(),
                // Menambahkan label 'Catatan'
                TextColumn::make('remarks')->label('Catatan')->searchable(),
                // Menambahkan label 'Dibuat Oleh'
                TextColumn::make('creator.name')->label('Dibuat Oleh')->searchable(),
            ])
            ->filters([
                // Rentang waktu occurred_at
                Filter::make('occurred_between')
                    ->label('Rentang Waktu')
                    ->form([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(EloquentBuilder $q, $date) =>
                                $q->whereDate('occurred_at', '>=', Carbon::parse($date)->startOfDay())
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn(EloquentBuilder $q, $date) =>
                                $q->whereDate('occurred_at', '<=', Carbon::parse($date)->endOfDay())
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['from'])) {
                            $indicators[] = 'Dari: ' . Carbon::parse($data['from'])->format('d M Y');
                        }
                        if (!empty($data['until'])) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['until'])->format('d M Y');
                        }

                        return $indicators;
                    })
            ])
            ->recordActions([
                ActionGroup::make([
                    // Label 'Cetak resi' sudah benar
                    Action::make('cetak_resi')
                        ->label('Cetak Packing Slip')
                        ->url(fn($record): string => route('inventory.cetak-resi', ['id' => $record->id]))
                        ->openUrlInNewTab()
                        ->visible(fn(): bool => auth()->user()->hasPermissionTo('viewAny-invoice'))
                        ->icon('heroicon-o-printer'),
                    // Mengubah label 'Edit' menjadi 'Ubah'
                    EditAction::make()->label('Ubah'),
                    // Mengubah label 'Delete' menjadi 'Hapus'
                    DeleteAction::make()->label('Hapus'),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Mengubah label 'Delete' menjadi 'Hapus Terpilih'
                    DeleteBulkAction::make()->label('Hapus Terpilih'),
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
