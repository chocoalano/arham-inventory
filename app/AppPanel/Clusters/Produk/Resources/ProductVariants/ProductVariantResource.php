<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductVariants;

use App\AppPanel\Clusters\Produk\ProdukCluster;
use App\AppPanel\Clusters\Produk\Resources\ProductVariants\Pages\CreateProductVariant;
use App\AppPanel\Clusters\Produk\Resources\ProductVariants\Pages\EditProductVariant;
use App\AppPanel\Clusters\Produk\Resources\ProductVariants\Pages\ListProductVariantActivities;
use App\AppPanel\Clusters\Produk\Resources\ProductVariants\Pages\ListProductVariants;
use App\AppPanel\Clusters\Produk\Resources\ProductVariants\Schemas\ProductVariantForm;
use App\AppPanel\Clusters\Produk\Resources\ProductVariants\Tables\ProductVariantsTable;
use App\Models\Inventory\ProductVariant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowUp;
    protected static ?string $cluster = ProdukCluster::class;
    protected static ?string $recordTitleAttribute = 'Variant';
    protected static ?string $modelLabel = 'Jenis/Varian Produk';
    protected static ?string $navigationLabel = 'Jenis/Varian Produk';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-product_variant', 'view-product_variant']);
    }
    public static function form(Schema $schema): Schema
    {
        return ProductVariantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductVariantsTable::configure($table);
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
            'index' => ListProductVariants::route('/'),
            'create' => CreateProductVariant::route('/create'),
            'edit' => EditProductVariant::route('/{record}/edit'),
            'activities' => ListProductVariantActivities::route('/{record}/activities'),
        ];
    }
}
