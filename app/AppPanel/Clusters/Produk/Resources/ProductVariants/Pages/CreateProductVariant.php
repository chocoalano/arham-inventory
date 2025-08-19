<?php

namespace App\AppPanel\Clusters\Produk\Resources\ProductVariants\Pages;

use App\AppPanel\Clusters\Produk\Resources\ProductVariants\ProductVariantResource;
use App\Models\Inventory\WarehouseVariantStock;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProductVariant extends CreateRecord
{
    protected static string $resource = ProductVariantResource::class;
    protected function handleRecordCreation(array $data): Model
    {
        $create = parent::handleRecordCreation($data);
        $qty = (int) ($data['qty'] ?? 0);

        WarehouseVariantStock::updateOrCreate(
            [
                'warehouse_id' => $data['set_warehouse'],
                'product_variant_id' => $create->id,
            ],
            [
                'qty' => $qty,
                'reserved_qty' => $data['reserved_qty'] ?? 0,
            ]
        );
        return $create;
    }
}
