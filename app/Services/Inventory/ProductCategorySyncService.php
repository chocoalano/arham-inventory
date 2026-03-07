<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProductCategory as InventoryProductCategory;
use Illuminate\Support\Facades\DB;

class ProductCategorySyncService
{
    private const ECOMMERCE_CONNECTION = 'olstore';

    /**
     * Sinkronkan 1 kategori inventory ke DB ecommerce (upsert by ID).
     */
    public function syncFromInventory(InventoryProductCategory $inventoryCategory, array $syncedIds = []): void
    {
        $categoryId = (int) $inventoryCategory->getKey();

        if ($categoryId <= 0 || isset($syncedIds[$categoryId])) {
            return;
        }

        $syncedIds[$categoryId] = true;

        $this->syncParentIfNeeded($inventoryCategory, $syncedIds);

        $payload = $this->buildUpsertPayload($inventoryCategory);

        DB::connection(self::ECOMMERCE_CONNECTION)->transaction(function () use ($categoryId, $payload): void {
            $table = DB::connection(self::ECOMMERCE_CONNECTION)->table('product_categories');
            $exists = $table->where('id', $categoryId)->exists();

            if ($exists) {
                $table->where('id', $categoryId)->update($payload);

                return;
            }

            $table->insert(array_merge(['id' => $categoryId], $payload));
        });
    }

    /**
     * Terapkan soft delete inventory ke kategori ecommerce yang terhubung.
     */
    public function softDeleteFromInventory(InventoryProductCategory $inventoryCategory): void
    {
        $deletedAt = $inventoryCategory->deleted_at ?? now();

        DB::connection(self::ECOMMERCE_CONNECTION)
            ->table('product_categories')
            ->where('id', $inventoryCategory->getKey())
            ->update([
                'deleted_at' => $deletedAt,
                'updated_at' => $inventoryCategory->updated_at ?? $deletedAt,
            ]);
    }

    /**
     * Restore kategori ecommerce saat kategori inventory direstore.
     */
    public function restoreFromInventory(InventoryProductCategory $inventoryCategory): void
    {
        $this->syncFromInventory($inventoryCategory);
    }

    /**
     * Hapus permanen kategori ecommerce saat inventory force delete.
     */
    public function forceDeleteFromInventory(InventoryProductCategory $inventoryCategory): void
    {
        DB::connection(self::ECOMMERCE_CONNECTION)
            ->table('product_categories')
            ->where('id', $inventoryCategory->getKey())
            ->delete();
    }

    private function syncParentIfNeeded(InventoryProductCategory $inventoryCategory, array &$syncedIds): void
    {
        $parentId = (int) ($inventoryCategory->parent_id ?? 0);

        if ($parentId <= 0 || isset($syncedIds[$parentId])) {
            return;
        }

        $parent = InventoryProductCategory::withTrashed()->find($parentId);

        if (! $parent) {
            return;
        }

        $this->syncFromInventory($parent, $syncedIds);
    }

    private function buildUpsertPayload(InventoryProductCategory $inventoryCategory): array
    {
        return [
            'parent_id' => $inventoryCategory->parent_id,
            'name' => $inventoryCategory->name,
            'slug' => $inventoryCategory->slug,
            'description' => $inventoryCategory->description,
            'image_path' => $inventoryCategory->image_path,
            'is_active' => $inventoryCategory->is_active,
            // ecommerce sort_order unsigned, jadi negative value dicegah.
            'sort_order' => max(0, (int) $inventoryCategory->sort_order),
            'meta' => $this->encodeMeta($inventoryCategory->meta),
            'deleted_at' => null,
            'created_at' => $inventoryCategory->created_at ?? now(),
            'updated_at' => $inventoryCategory->updated_at ?? now(),
        ];
    }

    private function encodeMeta(mixed $meta): ?string
    {
        if ($meta === null) {
            return null;
        }

        if (is_string($meta)) {
            return $meta;
        }

        $json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? null : $json;
    }
}
