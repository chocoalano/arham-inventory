<?php

namespace App\Observers\Inventory;

use App\Models\Inventory\Product;
use App\Services\Inventory\ProductSyncService;

class ProductObserver
{
    public function __construct(
        private readonly ProductSyncService $productSyncService
    ) {}

    public function created(Product $product): void
    {
        $this->productSyncService->syncFromInventory($product->fresh() ?? $product);
    }

    public function updated(Product $product): void
    {
        $this->productSyncService->syncFromInventory($product->fresh() ?? $product);
    }

    public function deleted(Product $product): void
    {
        if ($product->isForceDeleting()) {
            return;
        }

        $this->productSyncService->softDeleteFromInventory($product);
    }

    public function restored(Product $product): void
    {
        $this->productSyncService->restoreFromInventory($product->fresh() ?? $product);
    }

    public function forceDeleted(Product $product): void
    {
        $this->productSyncService->forceDeleteFromInventory($product);
    }
}
