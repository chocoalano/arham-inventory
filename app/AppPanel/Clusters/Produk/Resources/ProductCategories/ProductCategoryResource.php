<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductCategories;

use App\AppPanel\Clusters\Produk\ProdukCluster;
use App\AppPanel\Clusters\Produk\Resources\ProductCategories\Pages\CreateProductCategory;
use App\AppPanel\Clusters\Produk\Resources\ProductCategories\Pages\EditProductCategory;
use App\AppPanel\Clusters\Produk\Resources\ProductCategories\Pages\ListProductCategories;
use App\AppPanel\Clusters\Produk\Resources\ProductCategories\Pages\ViewProductCategory;
use App\AppPanel\Clusters\Produk\Resources\ProductCategories\Schemas\ProductCategoryForm;
use App\AppPanel\Clusters\Produk\Resources\ProductCategories\Schemas\ProductCategoryInfolist;
use App\AppPanel\Clusters\Produk\Resources\ProductCategories\Tables\ProductCategoriesTable;
use App\Models\Inventory\ProductCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = ProdukCluster::class;

    public static function form(Schema $schema): Schema
    {
        return ProductCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductCategoriesTable::configure($table);
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
            'index' => ListProductCategories::route('/'),
            'create' => CreateProductCategory::route('/create'),
            'view' => ViewProductCategory::route('/{record}'),
            'edit' => EditProductCategory::route('/{record}/edit'),
        ];
    }
}
