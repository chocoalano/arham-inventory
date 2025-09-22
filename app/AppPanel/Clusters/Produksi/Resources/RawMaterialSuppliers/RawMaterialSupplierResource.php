<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialSuppliers;

use App\AppPanel\Clusters\Produksi\ProduksiCluster;
use App\AppPanel\Clusters\Produksi\Resources\RawMaterialSuppliers\Pages\ManageRawMaterialSuppliers;
use App\Models\RawMaterial\RawMaterialSupplier;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema; // mengikuti pola proyek kamu
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class RawMaterialSupplierResource extends Resource
{
    protected static ?string $model = RawMaterialSupplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $cluster = ProduksiCluster::class;

    // Tampilkan nama bahan baku + nama pemasok sebagai judul
    protected static ?string $recordTitleAttribute = 'id';

    public static function getRecordTitle(?object $record): ?string
    {
        if (! $record) {
            return null;
        }

        $rm  = $record->rawMaterial?->name ?? $record->raw_material_id;
        $sup = $record->supplier?->name ?? $record->supplier_id;

        return "{$rm} — {$sup}";
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("Pemasok")
                ->description("Lengkapi form informasi pemasok untuk tiap bahan baku.")
                ->schema([
                    Select::make('raw_material_id')
                        ->label('Bahan Baku')
                        ->helperText('Pilih bahan baku yang dikaitkan dengan pemasok ini. Klik ikon ➕ untuk menambahkan bahan baku baru.')
                        ->relationship('material', 'name')
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

                    Select::make('supplier_id')
                        ->label('Pemasok')
                        ->helperText('Pilih pemasok yang menyediakan bahan baku ini. Klik ikon ➕ untuk menambahkan pemasok baru.')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->prefixAction(
                            fn () => Action::make('createSupplier')
                                ->icon('heroicon-o-plus')
                                ->tooltip('Tambah Pemasok Baru')
                                ->url(route('filament.app.produk.resources.suppliers.index'))
                                ->openUrlInNewTab()
                        ),

                    TextInput::make('supplier_sku')
                        ->label('Kode SKU Pemasok (Opsional)')
                        ->helperText('Masukkan kode SKU sesuai yang diberikan oleh pemasok.')
                        ->maxLength(64)
                        ->nullable(),

                    Toggle::make('is_preferred')
                        ->label('Pemasok Utama')
                        ->helperText('Aktifkan jika pemasok ini menjadi default utama untuk bahan baku.')
                        ->default(false)
                        ->required(),
                ]),
            Section::make('Harga Bahan Baku')
                ->description('Kelola daftar harga berdasarkan satuan dan periode berlaku.')
                ->relationship('prices')
                ->schema([
                    Select::make('unit_id')
                        ->label('Satuan (UoM)')
                        ->helperText('Pilih satuan harga bahan baku (misalnya KG, Liter, Dus). Klik ikon ➕ untuk menambahkan satuan baru.')
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

                    TextInput::make('price')
                        ->label('Harga Satuan')
                        ->helperText('Masukkan harga per satuan bahan baku sesuai dengan pemasok yang dipilih.')
                        ->required()
                        ->numeric()
                        ->default(0.0)
                        ->prefix('Rp'),
                    DatePicker::make('valid_from')
                        ->label('Berlaku Mulai')
                        ->helperText('Tanggal mulai harga ini berlaku.')
                        ->required(),

                    DatePicker::make('valid_to')
                        ->label('Berlaku Hingga (Opsional)')
                        ->helperText('Tanggal akhir harga berlaku. Biarkan kosong jika harga berlaku tanpa batas.'),
            ])
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('material.name')
                ->label('Bahan Baku'),
            TextEntry::make('supplier.name')
                ->label('Pemasok'),
            TextEntry::make('supplier_sku')
                ->label('SKU Pemasok'),
            IconEntry::make('is_preferred')
                ->label('Pemasok Utama')
                ->boolean(),
            TextEntry::make('created_at')
                ->label('Dibuat')
                ->dateTime(),
            TextEntry::make('updated_at')
                ->label('Diperbarui')
                ->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('material.code')
                    ->label('Kode BB')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('material.name')
                    ->label('Bahan Baku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Pemasok')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_sku')
                    ->label('SKU Pemasok')
                    ->searchable(),
                IconColumn::make('is_preferred')
                    ->label('Utama')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // contoh filter Pemasok Utama
                \Filament\Tables\Filters\TernaryFilter::make('is_preferred')
                    ->label('Pemasok Utama'),
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
            'index' => ManageRawMaterialSuppliers::route('/'),
        ];
    }
}
