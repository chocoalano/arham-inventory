<?php

namespace App\AppPanel\Clusters\Produksi\Resources\ProductBoms;

use App\AppPanel\Clusters\Produksi\ProduksiCluster;
use App\AppPanel\Clusters\Produksi\Resources\ProductBoms\Components\FormBomItem;
use App\AppPanel\Clusters\Produksi\Resources\ProductBoms\Components\FormBom;
use App\AppPanel\Clusters\Produksi\Resources\ProductBoms\Components\FormOperationalCost;
use App\AppPanel\Clusters\Produksi\Resources\ProductBoms\Pages\ManageProductBoms;
use App\Models\RawMaterial\ProductBom;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductBomResource extends Resource
{
    protected static ?string $model = ProductBom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    protected static ?string $cluster = ProduksiCluster::class;

    protected static ?string $recordTitleAttribute = 'ProductBom';
    public static function getModelLabel(): string
    {
        return 'BOM Produk';
    }

    public static function getPluralModelLabel(): string
    {
        return 'BOM Produk';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                            ->description("Form BOM produk")
                            ->schema(FormBom::form())
                            ->columns(2)
                            ->columnSpanFull(),
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema(FormBomItem::form())
                            ->columns(4)
                            ->columnSpanFull(),
                        Repeater::make('operational_costs')
                            ->relationship('operationalCosts')
                            ->live()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Biaya Operasional')
                                    ->helperText('Masukkan nama biaya operasional yang mudah dikenali, misalnya: Listrik, Air, Transportasi.')
                                    ->required(),
                                TextInput::make('price')
                                    ->label('Harga')
                                    ->numeric()
                                    ->helperText('Isi dengan angka tanpa tanda pemisah atau simbol mata uang. Contoh: 15000')
                                    ->required()
                                    ->reactive(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                        Section::make()
                            ->description('Input Pajak (%) dan Summary Biaya Operational Cost')
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                TextInput::make('tax_percent')
                                    ->label('Pajak (%)')
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $costs = $get('operational_costs') ?? [];
                                        $taxPercent = (float) ($state ?? 0);
                                        $sum = collect($costs)->sum(fn($c) => (float) ($c['price'] ?? 0));
                                        $total = $sum + ($sum * $taxPercent / 100);
                                        $set('total_operational_cost', round($total, 2));
                                    }),
                                TextInput::make('total_operational_cost')
                                    ->label('Total Biaya Operational Cost')
                                    ->extraAttributes(['readonly' => true, 'style' => 'background-color: #f0f0f0;'])
                                    ->reactive()
                                    ->default(fn ($get) => 0)
                                    ->afterStateHydrated(function ($set, $get, $state) {
                                        $costs = $get('operational_costs') ?? [];
                                        $taxPercent = (float) ($get('tax_percent') ?? 0);
                                        $sum = collect($costs)->sum(fn($c) => (float) ($c['price'] ?? 0));
                                        $total = $sum + ($sum * $taxPercent / 100);
                                        $set('total_operational_cost', round($total, 2));
                                    })
                                    ->afterStateUpdated(function ($set, $get, $state) {
                                        $costs = $get('operational_costs') ?? [];
                                        $taxPercent = (float) ($get('tax_percent') ?? 0);
                                        $sum = collect($costs)->sum(fn($c) => (float) ($c['price'] ?? 0));
                                        $total = $sum + ($sum * $taxPercent / 100);
                                        $set('total_operational_cost', round($total, 2));
                                    }),
                            ]),
                ]);
            }
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('product.sku')->label('SKU Produk'),
                TextEntry::make('productVariant.sku_variant')->label('SKU Varian Produk'),
                TextEntry::make('version'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('note'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ProductBom')
            ->columns([
                TextColumn::make('product.sku')
                    ->searchable(),
                TextColumn::make('productVariant.sku_variant')
                    ->searchable(),
                TextColumn::make('version')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('note')
                    ->searchable(),
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
            'index' => ManageProductBoms::route('/'),
        ];
    }
}
