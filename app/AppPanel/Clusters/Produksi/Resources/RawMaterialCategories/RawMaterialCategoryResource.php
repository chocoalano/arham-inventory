<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialCategories;

use App\AppPanel\Clusters\Produksi\ProduksiCluster;
use App\AppPanel\Clusters\Produksi\Resources\RawMaterialCategories\Pages\ManageRawMaterialCategories;
use App\Models\RawMaterial\RawMaterialCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RawMaterialCategoryResource extends Resource
{
    protected static ?string $model = RawMaterialCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Folder;

    protected static ?string $cluster = ProduksiCluster::class;

    protected static ?string $recordTitleAttribute = 'RawMaterialCategory';
    public static function getModelLabel(): string
    {
        return 'Kategori Bahan Baku';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kategori Bahan Baku';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kategori')
                    ->helperText('Masukkan nama kategori bahan baku atau produk, misalnya “Bahan Kimia”, “Kemasan”, atau “Biji Kopi”.')
                    ->required()
                    ->maxLength(120),
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->helperText('Jika aktif, kategori ini dapat digunakan. Jika tidak aktif, kategori akan disembunyikan dari pilihan.')
                    ->default(true)
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->helperText('Tambahkan deskripsi singkat tentang kategori ini, misalnya kegunaan atau catatan tambahan.')
                    ->nullable()
                    ->columnSpanFull(),

            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('description'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('RawMaterialCategory')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
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
            'index' => ManageRawMaterialCategories::route('/'),
        ];
    }
}
