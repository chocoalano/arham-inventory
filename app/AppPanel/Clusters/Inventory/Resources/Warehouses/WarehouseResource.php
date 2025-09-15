<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Warehouses;

use App\AppPanel\Clusters\Inventory\InventoryCluster;
use App\AppPanel\Clusters\Inventory\Resources\Warehouses\Pages\ListWarehouseActivities;
use App\AppPanel\Clusters\Inventory\Resources\Warehouses\Pages\ManageWarehouses;
use App\AppPanel\Clusters\Inventory\Resources\Warehouses\Schema\Form;
use App\Models\Inventory\Warehouse;
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
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\Cast\Bool_;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingLibrary;
    protected static ?string $cluster = InventoryCluster::class;
    protected static ?string $modelLabel = 'Gudang Penyimpanan';
    protected static ?string $navigationLabel = 'Gudang Penyimpanan';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-warehouse', 'view-warehouse']);
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(Form::schemaForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Gudang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Gudang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Alamat Gudang')
                    ->searchable()
                    ->toggleable()
                    ->limit(60)
                    ->sortable(),

                TextColumn::make('lat')
                    ->label('Lat')
                    ->formatStateUsing(fn($state) => is_null($state) ? '-' : number_format((float) $state, 6))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('lng')
                    ->label('Lng')
                    ->formatStateUsing(fn($state) => is_null($state) ? '-' : number_format((float) $state, 6))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('stocks_sum_qty')
                    ->label('Jumlah Item Tersimpan')
                    ->getStateUsing(fn($record) => $record->stocks->sum('qty'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->since() // tampil “x menit/jam lalu” di hover
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                /**
                 * Punya lokasi (efisien): cek lat/lng bukan NULL
                 * (daripada kolom 'location' yang tidak ada)
                 */
                TernaryFilter::make('has_location')
                    ->label('Punya Lokasi')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn(Builder $q) => $q->whereNotNull('lat')->whereNotNull('lng'),
                        false: fn(Builder $q) => $q->whereNull('lat')->orWhereNull('lng'),
                    ),

                /**
                 * Aktif / Tidak
                 */
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->queries(
                        true: fn(Builder $q) => $q->where('is_active', true),
                        false: fn(Builder $q) => $q->where('is_active', false),
                    ),

                /**
                 * Kota & Provinsi (distinct → SelectFilter, searchable & preload)
                 */
                SelectFilter::make('city')
                    ->label('Kota')
                    ->options(
                        fn() => Warehouse::query()
                            ->whereNotNull('city')
                            ->distinct()
                            ->orderBy('city')
                            ->pluck('city', 'city')
                            ->all()
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('province')
                    ->label('Provinsi')
                    ->options(
                        fn() => Warehouse::query()
                            ->whereNotNull('province')
                            ->distinct()
                            ->orderBy('province')
                            ->pluck('province', 'province')
                            ->all()
                    )
                    ->searchable()
                    ->preload(),

                /**
                 * Rentang tanggal dibuat (tanpa fungsi di kolom → sargable)
                 */
                Filter::make('created_between')
                    ->label('Dibuat antara')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])->columns(2)
                    ->query(function (Builder $q, array $data): Builder {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        return $q
                            ->when($from, fn(Builder $qq) => $qq->where('created_at', '>=', $from))
                            // batas atas eksklusif agar mencakup full hari
                            ->when($until, fn(Builder $qq) => $qq->where('created_at', '<', \Illuminate\Support\Carbon::parse($until)->addDay()->startOfDay()));
                    })
                    ->columnSpan(3),

                /**
                 * Ada stok? (cek relasi stocks.qty > 0)
                 */
                TernaryFilter::make('has_stock')
                    ->label('Ada Stok')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn(Builder $q) => $q->whereHas('stocks', fn($s) => $s->where('qty', '>', 0)),
                        false: fn(Builder $q) => $q->whereDoesntHave('stocks', fn($s) => $s->where('qty', '>', 0)),
                    ),

                TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ActionGroup::make([
                    Action::make('activities')
                        ->label('Aktivitas')
                        ->icon('heroicon-m-clock')
                        ->color('primary')
                        ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin'))
                        ->url(fn($record) => WarehouseResource::getUrl('activities', ['record' => $record])),
                    ViewAction::make()
                        ->schema([
                            Section::make('Informasi Gudang Penyimpanan')
                                ->description('Bagian ini menampilkan informasi dasar mengenai gudang penyimpanan.')
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('code')->label('Kode'),
                                    TextEntry::make('name')->label('Nama'),
                                    TextEntry::make('address')->label('Alamat'),
                                    TextEntry::make('district')->label('Kecamatan'),
                                    TextEntry::make('city')->label('Kota'),
                                    TextEntry::make('province')->label('Provinsi'),
                                    TextEntry::make('postal_code')->label('Kode Pos'),
                                    TextEntry::make('lat')->label('Garis Lintang (Lat)'),
                                    TextEntry::make('lng')->label('Garis Bujur (Lng)'),
                                    TextEntry::make('phone')->label('Telepon'),
                                    TextEntry::make('is_active')
                                        ->label('Status Aktif')
                                        ->formatStateUsing(fn($state): string => $state ? 'Aktif' : 'Tidak Aktif')
                                        ->badge()
                                        ->color(fn($state) => $state ? 'success' : 'danger'),
                                ]),

                            Section::make('Informasi Barang Tersimpanan')
                                ->description('Daftar varian dan jumlah stok yang tersimpan di gudang ini.')
                                ->columns(1)
                                ->schema([
                                    // Tampilkan daftar stok bila ada
                                    RepeatableEntry::make('stocks')
                                        ->label('Stok per Varian')
                                        ->columns(4)
                                        ->schema([
                                            TextEntry::make('variant.product.name')->label('Nama Produk')->placeholder('-'),
                                            TextEntry::make('variant.product.sku')->label('SKU Produk')->placeholder('-'),
                                            TextEntry::make('variant.color')->label('Varian Warna Produk')->placeholder('-'),
                                            TextEntry::make('variant.size')->label('Varian Ukuran Produk')->placeholder('-'),
                                            TextEntry::make('variant.sku_variant')->label('SKU Varian')->placeholder('-'),
                                            TextEntry::make('qty')->label('Qty')->badge()->color('info'),
                                            TextEntry::make('reserved_qty')->label('Reservasi')->badge()->color('warning'),
                                            TextEntry::make('available')
                                                ->label('Tersedia')
                                                ->state(fn($record) => max(0, (int) $record->qty - (int) $record->reserved_qty))
                                                ->badge()
                                                ->color(fn($state) => $state > 0 ? 'success' : 'danger'),
                                        ])
                                        ->visible(fn($record) => $record->stocks && $record->stocks->isNotEmpty()),

                                    // Placeholder saat kosong
                                    Placeholder::make('stocks_empty')
                                        ->label('Stok per Varian')
                                        ->content('Gudang ini belum memiliki stok varian.')
                                        ->visible(fn($record) => !$record->stocks || $record->stocks->isEmpty()),
                                ]),
                        ]),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ReplicateAction::make(),
                    ForceDeleteAction::make()
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make()
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWarehouses::route('/'),
            'activities' => ListWarehouseActivities::route('/{record}/activities'),
        ];
    }
}
