<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Product as InventoryProduct;
use App\Models\Inventory\ProductCategory as InventoryProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSyncService
{
    private const ECOMMERCE_CONNECTION = 'olstore';

    public function __construct(
        private readonly ProductCategorySyncService $productCategorySyncService
    ) {}

    /**
     * Sinkronkan produk inventory ke ecommerce (upsert by product ID).
     */
    public function syncFromInventory(InventoryProduct $inventoryProduct): void
    {
        $productId = (int) $inventoryProduct->getKey();

        if ($productId <= 0) {
            return;
        }

        $this->syncCategoryIfNeeded($inventoryProduct);

        DB::connection(self::ECOMMERCE_CONNECTION)->transaction(function () use ($productId, $inventoryProduct): void {
            $db = DB::connection(self::ECOMMERCE_CONNECTION);
            $payload = $this->buildUpsertPayload($inventoryProduct);

            $skuConflict = $db->table('products')
                ->where('sku', $inventoryProduct->sku)
                ->where('id', '!=', $productId)
                ->value('id');

            if ($skuConflict) {
                throw new \RuntimeException("SKU {$inventoryProduct->sku} sudah dipakai produk ecommerce ID {$skuConflict}.");
            }

            $exists = $db->table('products')->where('id', $productId)->exists();

            if ($exists) {
                $db->table('products')->where('id', $productId)->update($payload);
            } else {
                $db->table('products')->insert(array_merge([
                    'id' => $productId,
                    'created_at' => $inventoryProduct->created_at ?? now(),
                ], $payload));
            }

            $this->syncCategoryPivot($productId, $inventoryProduct->product_category_id);
        });
    }

    /**
     * Terapkan soft-delete inventory ke ecommerce.
     */
    public function softDeleteFromInventory(InventoryProduct $inventoryProduct): void
    {
        $deletedAt = $inventoryProduct->deleted_at ?? now();

        DB::connection(self::ECOMMERCE_CONNECTION)
            ->table('products')
            ->where('id', $inventoryProduct->getKey())
            ->update([
                'deleted_at' => $deletedAt,
                'updated_at' => $inventoryProduct->updated_at ?? $deletedAt,
            ]);
    }

    public function restoreFromInventory(InventoryProduct $inventoryProduct): void
    {
        $this->syncFromInventory($inventoryProduct);
    }

    public function forceDeleteFromInventory(InventoryProduct $inventoryProduct): void
    {
        DB::connection(self::ECOMMERCE_CONNECTION)
            ->table('products')
            ->where('id', $inventoryProduct->getKey())
            ->delete();
    }

    private function buildUpsertPayload(InventoryProduct $inventoryProduct): array
    {
        return [
            'sku' => $inventoryProduct->sku,
            'name' => $inventoryProduct->name,
            'slug' => $this->buildUniqueProductSlug($inventoryProduct),
            'short_description' => $this->buildShortDescription($inventoryProduct),
            'description' => $inventoryProduct->description,
            'brand_id' => $this->resolveBrandId($inventoryProduct),
            'status' => $inventoryProduct->is_active ? 'active' : 'archived',
            'deleted_at' => null,
            'updated_at' => $inventoryProduct->updated_at ?? now(),
        ];
    }

    private function buildShortDescription(InventoryProduct $inventoryProduct): ?string
    {
        if (filled($inventoryProduct->model)) {
            return $inventoryProduct->model;
        }

        if (blank($inventoryProduct->description)) {
            return null;
        }

        return Str::limit(trim(strip_tags((string) $inventoryProduct->description)), 255, '');
    }

    private function buildUniqueProductSlug(InventoryProduct $inventoryProduct): string
    {
        $seed = trim(($inventoryProduct->name ?? '') . ' ' . ($inventoryProduct->sku ?? ''));
        $base = Str::slug($seed);

        if ($base === '') {
            $base = 'product-' . $inventoryProduct->getKey();
        }

        return $this->buildUniqueSlug('products', $base, (int) $inventoryProduct->getKey());
    }

    private function resolveBrandId(InventoryProduct $inventoryProduct): ?int
    {
        $brandName = trim((string) ($inventoryProduct->brand ?? ''));

        if ($brandName === '') {
            return null;
        }

        $db = DB::connection(self::ECOMMERCE_CONNECTION);

        $existingId = $db->table('brands')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($brandName)])
            ->value('id');

        if ($existingId) {
            $db->table('brands')
                ->where('id', $existingId)
                ->update([
                    'name' => $brandName,
                    'is_active' => true,
                    'deleted_at' => null,
                    'updated_at' => $inventoryProduct->updated_at ?? now(),
                ]);

            return (int) $existingId;
        }

        $slug = $this->buildUniqueSlug('brands', Str::slug($brandName) ?: 'brand', 0);

        return (int) $db->table('brands')->insertGetId([
            'name' => $brandName,
            'slug' => $slug,
            'is_active' => true,
            'created_at' => $inventoryProduct->created_at ?? now(),
            'updated_at' => $inventoryProduct->updated_at ?? now(),
        ]);
    }

    private function buildUniqueSlug(string $table, string $base, int $ignoreId): string
    {
        $db = DB::connection(self::ECOMMERCE_CONNECTION);
        $baseSlug = Str::limit($base, 240, '');
        $slug = $baseSlug;
        $counter = 2;

        while (
            $db->table($table)
                ->when($ignoreId > 0, fn($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $suffix = '-' . $counter;
            $slug = Str::limit($baseSlug, 255 - mb_strlen($suffix), '') . $suffix;
            $counter++;
        }

        return $slug;
    }

    private function syncCategoryIfNeeded(InventoryProduct $inventoryProduct): void
    {
        $categoryId = (int) ($inventoryProduct->product_category_id ?? 0);

        if ($categoryId <= 0) {
            return;
        }

        $category = InventoryProductCategory::withTrashed()->find($categoryId);

        if (! $category) {
            return;
        }

        $this->productCategorySyncService->syncFromInventory($category);
    }

    private function syncCategoryPivot(int $productId, ?int $categoryId): void
    {
        $db = DB::connection(self::ECOMMERCE_CONNECTION);

        $db->table('product_category_product')
            ->where('product_id', $productId)
            ->delete();

        $validCategoryId = (int) ($categoryId ?? 0);

        if ($validCategoryId <= 0) {
            return;
        }

        $categoryExists = $db->table('product_categories')
            ->where('id', $validCategoryId)
            ->exists();

        if (! $categoryExists) {
            return;
        }

        $db->table('product_category_product')->insert([
            'product_id' => $productId,
            'product_category_id' => $validCategoryId,
        ]);
    }
}
