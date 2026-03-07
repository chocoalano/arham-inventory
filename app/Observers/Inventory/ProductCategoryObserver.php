<?php

namespace App\Observers\Inventory;

use App\Models\Inventory\ProductCategory;
use App\Services\Inventory\ProductCategorySyncService;

class ProductCategoryObserver
{
    public function __construct(
        private readonly ProductCategorySyncService $syncService
    ) {}

    public function created(ProductCategory $productCategory): void
    {
        $this->syncService->syncFromInventory($productCategory->fresh() ?? $productCategory);
    }

    public function updated(ProductCategory $productCategory): void
    {
        $this->syncService->syncFromInventory($productCategory->fresh() ?? $productCategory);
    }

    public function deleted(ProductCategory $productCategory): void
    {
        if ($productCategory->isForceDeleting()) {
            return;
        }

        $this->syncService->softDeleteFromInventory($productCategory);
    }

    public function restored(ProductCategory $productCategory): void
    {
        $this->syncService->restoreFromInventory($productCategory->fresh() ?? $productCategory);
    }

    public function forceDeleted(ProductCategory $productCategory): void
    {
        $this->syncService->forceDeleteFromInventory($productCategory);
    }
}
