<?php

namespace App\AppPanel\Clusters\Produksi\Resources\RawMaterialBatches;

use App\AppPanel\Clusters\Produksi\ProduksiCluster;
use App\AppPanel\Clusters\Produksi\Resources\RawMaterialBatches\Pages\ManageRawMaterialBatches;
use App\Models\RawMaterial\RawMaterialBatch;
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
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RawMaterialBatchResource extends Resource
{
    protected static ?string $model = RawMaterialBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocument;

    protected static ?string $cluster = ProduksiCluster::class;

    protected static ?string $recordTitleAttribute = 'RawMaterialBatch';
    public static function getModelLabel(): string
    {
        return 'Batch Bahan Baku';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Batch Bahan Baku';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('raw_material_id')
                    ->label('Bahan Baku')
                    ->helperText('Pilih bahan baku yang akan dibuat batch/lot. Jika belum ada, klik ikon ➕ untuk menambahkan bahan baku baru.')
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

                TextInput::make('lot_no')
                    ->label('Nomor Batch / Lot')
                    ->helperText('Masukkan nomor batch atau lot bahan baku. Nomor ini digunakan untuk melacak stok dan kedaluwarsa.')
                    ->required(),

                DatePicker::make('mfg_date')
                    ->label('Tanggal Produksi')
                    ->helperText('Tanggal bahan baku ini diproduksi. Opsional, tetapi disarankan untuk tracking QA.'),

                DatePicker::make('exp_date')
                    ->label('Tanggal Kedaluwarsa')
                    ->helperText('Tanggal kedaluwarsa bahan baku. Wajib diisi untuk bahan baku yang mudah rusak atau terbatas masa pakainya.'),

                ToggleButtons::make('quality_status')
                    ->label('Status Kualitas')
                    ->helperText('Tentukan status kualitas batch bahan baku: Released = bisa dipakai, On Hold = ditahan sementara, Rejected = ditolak.')
                    ->options([
                        'released' => 'Released',
                        'on_hold'  => 'On Hold',
                        'rejected' => 'Rejected',
                    ])
                    ->grouped()
                    ->default('released'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('rawMaterial.name'),
                TextEntry::make('lot_no'),
                TextEntry::make('mfg_date')
                    ->date(),
                TextEntry::make('exp_date')
                    ->date(),
                TextEntry::make('quality_status'),
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
            ->recordTitleAttribute('RawMaterialBatch')
            ->columns([
                TextColumn::make('rawMaterial.name')
                    ->searchable(),
                TextColumn::make('lot_no')
                    ->searchable(),
                TextColumn::make('mfg_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('exp_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('quality_status')
                    ->searchable(),
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
            'index' => ManageRawMaterialBatches::route('/'),
        ];
    }
}
