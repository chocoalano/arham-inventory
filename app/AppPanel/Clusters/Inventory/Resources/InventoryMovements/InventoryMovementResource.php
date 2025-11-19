<?php

namespace App\AppPanel\Clusters\Inventory\Resources\InventoryMovements;

use App\AppPanel\Clusters\Inventory\InventoryCluster;
use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Pages\ManageInventoryActivities;
use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Pages\ManageInventoryMovements;
use App\AppPanel\Clusters\Inventory\Resources\InventoryMovements\Widgets\InventoryStats;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseVariantStock;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Truck;
    protected static ?string $cluster = InventoryCluster::class;
    protected static ?string $recordTitleAttribute = 'inventoryMovement';
    protected static ?string $modelLabel = 'Perpindahan Stok Barang';
    protected static ?string $navigationLabel = 'Perpindahan Stok Barang';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-transaction', 'view-transaction']);
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Perpindahan Stok Barang')
                    ->description('Catat perpindahan stok antar gudang. Pilih gudang sumber & tujuan, varian yang dipindahkan, lalu tentukan jumlah dan waktu perpindahan.')
                    ->columns(2)
                    ->schema([
                        // Dari Gudang (Sumber)
                        Select::make('source_warehouse_id')
                            ->label('Dari Gudang')
                            ->helperText('Gudang sumber tempat stok akan dikurangi.')
                            ->options(function () {
                                $user = Auth::user();
                                if (!$user)
                                    return [];

                                return Warehouse::query()
                                    ->forUser($user)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn() => Auth::user()?->warehouse_id)
                            ->disabled(fn() => Auth::user() && !Auth::user()->hasRole('Superadmin')) // “readonly” utk Select
                            ->dehydrated()
                            ->dehydrateStateUsing(function ($state) {
                                // Hardening: non-superadmin dipaksa ke gudangnya sendiri
                                $user = Auth::user();
                                return ($user && !$user->hasRole('Superadmin')) ? $user->warehouse_id : $state;
                            })
                            ->reactive(), // agar field lain refresh ketika sumber berubah

                        // Ke Gudang (Tujuan)
                        Select::make('destination_warehouse_id')
                            ->label('Ke Gudang')
                            ->helperText('Gudang tujuan tempat stok akan ditambahkan. Tidak boleh sama dengan gudang sumber.')
                            ->options(function (Get $get) {
                                $src = (int) ($get('source_warehouse_id') ?? 0);

                                $wh_target = Warehouse::query()
                                    ->when($src > 0, fn($q) => $q->where('id', '!=', $src))
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                                    array_push($wh_target, 'Online Store');
                                return $wh_target;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rules(['different:source_warehouse_id'])
                            ->reactive(),

                        // Varian Produk
                        Select::make('product_variant_id')
                            ->label('Varian')
                            ->helperText('Pilih varian yang akan dipindahkan. Daftar hanya menampilkan varian dengan stok tersedia di gudang sumber.')
                            ->options(function (Get $get) {
                                $user = Auth::user();
                                if (!$user)
                                    return [];

                                $src = (int) ($get('source_warehouse_id') ?? ($user->hasRole('Superadmin') ? 0 : ($user->warehouse_id ?? 0)));
                                $pid = $get('product_id'); // opsional: jika ada field produk di form

                                $q = ProductVariant::query()
                                    ->forUser($user) // sudah memfilter by gudang user & stok on-hand > 0
                                    ->when($pid, fn($qq) => $qq->where('product_id', $pid))
                                    ->when(
                                        $src > 0,
                                        fn($qq) =>
                                        $qq->whereHas('stocks', function ($s) use ($src) {
                                            $s->where('warehouse_id', $src)
                                                ->whereRaw('(COALESCE(qty,0) - COALESCE(reserved_qty,0)) > 0');
                                        })
                                    )
                                    ->orderBy('sku_variant');

                                return $q->pluck('sku_variant', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn(Get $get) => blank($get('source_warehouse_id')))
                            ->reactive(),

                        // Info stok tersedia (read-only)
                        Placeholder::make('available_stock_display')
                            ->label('Stok Tersedia di Gudang Sumber')
                            ->content(function (Get $get) {
                                $src = (int) ($get('source_warehouse_id') ?? 0);
                                $vid = (int) ($get('product_variant_id') ?? 0);
                                if ($src <= 0 || $vid <= 0) {
                                    return '—';
                                }

                                $soh = (int) (WarehouseVariantStock::query()
                                    ->where('warehouse_id', $src)
                                    ->where('product_variant_id', $vid)
                                    ->selectRaw('COALESCE(qty,0) - COALESCE(reserved_qty,0) AS soh')
                                    ->value('soh') ?? 0);

                                return number_format($soh, 0, ',', '.');
                            })
                            ->columnSpanFull()
                            ->reactive(),

                        // Jumlah yang dipindahkan
                        TextInput::make('qty_change')
                            ->label('Jumlah Dipindahkan')
                            ->helperText('Jumlah unit yang akan dipindahkan dari gudang sumber ke gudang tujuan.')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->maxValue(function (Get $get) {
                                $src = (int) ($get('source_warehouse_id') ?? 0);
                                $vid = (int) ($get('product_variant_id') ?? 0);
                                if ($src <= 0 || $vid <= 0)
                                    return null;

                                return (int) (WarehouseVariantStock::query()
                                    ->where('warehouse_id', $src)
                                    ->where('product_variant_id', $vid)
                                    ->selectRaw('COALESCE(qty,0) - COALESCE(reserved_qty,0) AS soh')
                                    ->value('soh') ?? 0);
                            })
                            ->reactive(),

                        // Jenis transaksi
                        TextInput::make('type')
                            ->label('Tipe')
                            ->default('pemindahan')
                            ->disabled()
                            ->helperText('Tipe transaksi dikunci sebagai pemindahan stok.'),

                        // Waktu perpindahan
                        DateTimePicker::make('occurred_at')
                            ->label('Waktu')
                            ->default(now())
                            ->helperText('Tanggal & waktu ketika perpindahan dianggap terjadi.'),

                        // Catatan tambahan
                        TextInput::make('remarks')
                            ->label('Catatan')
                            ->helperText('Opsional: catat alasan atau referensi dokumen.')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        $query = InventoryMovement::forUser(Auth::user())
            ->with([
                'transaction',
                'from_warehouse',
                'to_warehouse',
                'variant',
                'creator',
            ]);
        return $table
            ->query($query)
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
                TextColumn::make('type')->label('Jenis')->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                    }),
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
                    ->columns(2)
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
                    })->columnSpan(4),
                TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ActionGroup::make([
                    Action::make('activities')
                        ->label('Aktivitas')
                        ->icon('heroicon-m-clock')
                        ->color('primary')
                        ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin'))
                        ->url(fn($record): string => InventoryMovementResource::getUrl('activities', ['record' => $record])),
                    Action::make('cetak_resi')
                        ->label('Cetak Packing Slip')
                        ->url(fn($record): string => route('inventory.cetak-resi', ['id' => $record->id]))
                        ->openUrlInNewTab()
                        ->visible(fn(): bool => auth()->user()->hasPermissionTo('viewAny-invoice'))
                        ->icon('heroicon-o-printer'),
                    // Mengubah label 'Edit' menjadi 'Ubah'
                    EditAction::make()->label('Ubah'),
                    // Mengubah label 'Delete' menjadi 'Hapus'
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ReplicateAction::make(),
                    ForceDeleteAction::make()
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Mengubah label 'Delete' menjadi 'Hapus Terpilih'
                    DeleteBulkAction::make()->label('Hapus Terpilih'),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make()
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
            'activities' => ManageInventoryActivities::route('/{record}/activities'),
        ];
    }
}
