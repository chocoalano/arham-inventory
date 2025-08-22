<?php

namespace App\AppPanel\Clusters\Produk\Resources\Products;

use App\AppPanel\Clusters\Produk\ProdukCluster;
use App\AppPanel\Clusters\Produk\Resources\Products\Pages\CreateProduct;
use App\AppPanel\Clusters\Produk\Resources\Products\Pages\EditProduct;
use App\AppPanel\Clusters\Produk\Resources\Products\Pages\ListProducts;
use App\AppPanel\Clusters\Produk\Resources\Products\Schemas\ProductForm;
use App\AppPanel\Clusters\Produk\Resources\Products\Tables\ProductsTable;
use App\Models\Inventory\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static ?string $cluster = ProdukCluster::class;
    protected static ?string $recordTitleAttribute = 'Produk';
    protected static ?string $modelLabel = 'Produk';
    protected static ?string $navigationLabel = 'Produk';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-product', 'view-product']);
    }
    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
