<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Warehouses;

use App\AppPanel\Clusters\Inventory\InventoryCluster;
use App\AppPanel\Clusters\Inventory\Resources\Warehouses\Pages\ManageWarehouses;
use App\Models\Inventory\Warehouse;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = InventoryCluster::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Gudang')
                    ->description('Informasi dasar mengenai gudang, termasuk kode dan nama unik untuk identifikasi.') // Deskripsi
                    ->columns(3)
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->maxLength(32)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn() => app()->environment(['debug', 'local']) ? strtoupper(Str::random(6)) : null),
                        TextInput::make('name')
                            ->label('Nama Gudang')
                            ->maxLength(150)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn() => app()->environment(['debug', 'local']) ? fake()->city() . ' Warehouse' : null),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columnSpanFull(),
                Section::make('Lokasi & Kontak')
                    ->description('Informasi detail lokasi dan kontak yang dapat digunakan untuk keperluan pengiriman atau komunikasi.') // Deskripsi
                    ->columns(3)
                    ->schema([
                        TextInput::make('address')
                            ->label('Alamat')
                            ->columnSpan(3)
                            ->default(fn() => app()->environment(['debug', 'local']) ? fake()->streetAddress() : null), // Autofill alamat
                        TextInput::make('district')
                            ->label('Kecamatan')
                            ->default(fn() => app()->environment(['debug', 'local']) ? fake()->citySuffix() : null), // Autofill kecamatan
                        TextInput::make('city')
                            ->label('Kota')
                            ->default(fn() => app()->environment(['debug', 'local']) ? fake()->city() : null), // Autofill kota
                        TextInput::make('province')
                            ->label('Provinsi')
                            ->default(fn() => app()->environment(['debug', 'local']) ? fake()->state() : null), // Autofill provinsi
                        TextInput::make('postal_code')
                            ->label('Kode Pos')
                            ->maxLength(16)
                            ->default(fn() => app()->environment(['debug', 'local']) ? fake()->postcode() : null), // Autofill kode pos
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->maxLength(32)
                            ->default(fn() => app()->environment(['debug', 'local']) ? fake()->phoneNumber() : null), // Autofill nomor telepon
                        Grid::make(2)
                            ->schema([
                                TextInput::make('lat')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->rule('between:-90,90')
                                    ->default(fn() => app()->environment(['debug', 'local']) ? fake()->latitude() : null), // Autofill latitude
                                TextInput::make('lng')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->rule('between:-180,180')
                                    ->default(fn() => app()->environment(['debug', 'local']) ? fake()->longitude() : null), // Autofill longitude
                            ])
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('KOde Gudang')
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
                    ->label('Jumlah Stok')
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
                Filter::make('has_location')
                    ->label('Punya Lokasi')
                    ->query(fn($query) => $query->whereNotNull('location')->where('location', '<>', '')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWarehouses::route('/'),
        ];
    }
}
