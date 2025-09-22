<?php

namespace App\AppPanel\Clusters\Produksi\Resources\UnitConversations;

use App\AppPanel\Clusters\Produksi\ProduksiCluster;
use App\AppPanel\Clusters\Produksi\Resources\UnitConversations\Pages\ManageUnitConversations;
use App\Models\RawMaterial\UnitConversation;
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

class UnitConversationResource extends Resource
{
    protected static ?string $model = UnitConversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected static ?string $cluster = ProduksiCluster::class;

    protected static ?string $recordTitleAttribute = 'UnitConversation';

    /** Label singular/plural di UI */
    public static function getModelLabel(): string
    {
        return 'Unit Percakapan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Unit Percakapan';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('from_unit_id')
                    ->label('Dari Satuan (Asal)')
                    ->helperText('Pilih satuan asal yang akan dikonversi, misalnya Gram.')
                    ->relationship('from', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->prefixAction(
                        fn () => Action::make('createUnit')
                            ->icon('heroicon-o-plus')
                            ->tooltip('Tambah Unit Bahan Baku Baru')
                            ->url(route('filament.app.produksi.resources.units.index'))
                            ->openUrlInNewTab()
                    ),

                Select::make('to_unit_id')
                    ->label('Ke Satuan (Tujuan)')
                    ->helperText('Pilih satuan tujuan hasil konversi, misalnya Kilogram.')
                    ->relationship('to', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->prefixAction(
                        fn () => Action::make('createUnit')
                            ->icon('heroicon-o-plus')
                            ->tooltip('Tambah Unit Bahan Baku Baru')
                            ->url(route('filament.app.produksi.resources.units.index'))
                            ->openUrlInNewTab()
                    ),

                TextInput::make('factor')
                    ->label('Faktor Konversi')
                    ->helperText('Masukkan faktor konversi. Contoh: 0.001 untuk konversi dari Gram ke Kilogram.')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('from_unit_id')
                    ->numeric(),
                TextEntry::make('to_unit_id')
                    ->numeric(),
                TextEntry::make('factor')
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
            ->recordTitleAttribute('UnitConversation')
            ->columns([
                TextColumn::make('from_unit_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('to_unit_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('factor')
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
            'index' => ManageUnitConversations::route('/'),
        ];
    }
}
