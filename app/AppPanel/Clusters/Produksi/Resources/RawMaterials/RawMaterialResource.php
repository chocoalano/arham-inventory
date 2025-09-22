<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterials;

use App\AppPanel\Clusters\Produksi\ProduksiCluster;
use App\AppPanel\Clusters\Produksi\Resources\RawMaterials\Components\RawMaterialForm;
use App\AppPanel\Clusters\Produksi\Resources\RawMaterials\Components\RawMaterialImageForm;
use App\AppPanel\Clusters\Produksi\Resources\RawMaterials\Components\RawMaterialSupplierForm;
use App\AppPanel\Clusters\Produksi\Resources\RawMaterials\Pages\ManageRawMaterials;
use App\Models\RawMaterial\RawMaterial;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RawMaterialResource extends Resource
{
    protected static ?string $model = RawMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CursorArrowRipple;

    protected static ?string $cluster = ProduksiCluster::class;

    protected static ?string $recordTitleAttribute = 'RawMaterial';
    public static function getModelLabel(): string
    {
        return 'Bahan Baku';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Bahan Baku';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Bahan Baku')
                    ->description('Isi detail utama bahan baku, termasuk kategori, satuan utama, kode unik, nama, spesifikasi, dan status aktif.')
                    ->schema(RawMaterialForm::form())
                    ->columns(2)
                    ->columnSpanFull(),

                Repeater::make('images')
                    ->label('Gambar Bahan Baku')
                    ->helperText('Unggah satu atau lebih gambar untuk bahan baku ini. Tandai salah satu sebagai gambar utama.')
                    ->relationship('images')
                    ->schema(RawMaterialImageForm::form())
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('category.name'),
                TextEntry::make('defaultUnit.name'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('min_stock')
                    ->numeric(),
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
            ->recordTitleAttribute('RawMaterial')
            ->columns([
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('defaultUnit.name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('min_stock')
                    ->numeric()
                    ->sortable(),
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
            'index' => ManageRawMaterials::route('/'),
        ];
    }
}
