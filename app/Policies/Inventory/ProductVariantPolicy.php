<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\ProductVariant;
use App\Models\User;

class ProductVariantPolicy
{
    /**
     * Superadmin bypass semua ability.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Superadmin'])) {
            return true;
        }

        return null;
    }

    /** -----------------------
     *  Core abilities
     * ---------------------- */

    /** List / Index */
    public function viewAny(User $user): bool
    {
        return $this->perm($user, 'viewAny-productVariant');
    }

    /** View detail */
    public function view(User $user, ProductVariant $productVariant): bool
    {
        return $this->perm($user, 'view-productVariant') || $this->isOwner($user, $productVariant);
    }

    /** Create */
    public function create(User $user): bool
    {
        return $this->perm($user, 'create-productVariant');
    }

    /** Update / Edit */
    public function update(User $user, ProductVariant $productVariant): bool
    {
        return $this->perm($user, 'update-productVariant') || $this->isOwner($user, $productVariant);
    }

    /** Delete (soft delete) */
    public function delete(User $user, ProductVariant $productVariant): bool
    {
        if (! $this->canDeleteVariant($productVariant)) {
            return false;
        }

        return $this->perm($user, 'delete-productVariant');
    }

    /** Bulk delete (soft delete) */
    public function deleteAny(User $user): bool
    {
        return $this->perm($user, 'deleteAny-productVariant');
    }

    /** Restore (from soft delete) */
    public function restore(User $user, ProductVariant $productVariant): bool
    {
        return $this->perm($user, 'restore-productVariant');
    }

    /** Bulk restore */
    public function restoreAny(User $user): bool
    {
        return $this->perm($user, 'restoreAny-productVariant');
    }

    /** Force delete (permanent) */
    public function forceDelete(User $user, ProductVariant $productVariant): bool
    {
        if (! $this->canDeleteVariant($productVariant)) {
            return false;
        }

        return $this->perm($user, 'forceDelete-productVariant');
    }

    /** Bulk force delete (permanent) */
    public function forceDeleteAny(User $user): bool
    {
        return $this->perm($user, 'forceDeleteAny-productVariant');
    }

    /** Replicate (duplicate record) */
    public function replicate(User $user, ProductVariant $productVariant): bool
    {
        return $this->perm($user, 'replicate-productVariant') || $this->isOwner($user, $productVariant);
    }

    /** Export (custom) */
    public function export(User $user): bool
    {
        return $this->perm($user, 'export-productVariant');
    }

    /** Import (custom) */
    public function import(User $user): bool
    {
        return $this->perm($user, 'import-productVariant');
    }

    /* =========================
     * Helpers
     * ========================= */

    private function perm(User $user, string $permission): bool
    {
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
            return true;
        }
        return $user->can($permission);
    }

    /**
     * Owner/penanggung jawab varian (opsional).
     * Aktif jika model punya kolom user_id / created_by.
     */
    private function isOwner(User $user, ProductVariant $variant): bool
    {
        return (isset($variant->user_id) && (int) $variant->user_id === (int) $user->id)
            || (isset($variant->created_by) && (int) $variant->created_by === (int) $user->id);
    }

    /**
     * Cek apakah varian aman dihapus/force delete.
     * - Tidak terikat stok, transaksi detail, atau movement.
     * Defensif: hanya cek relasi yang tersedia di model.
     */
    private function canDeleteVariant(ProductVariant $variant): bool
    {
        // Stok per gudang/varian
        if (method_exists($variant, 'warehouseStocks') && $variant->warehouseStocks()->exists()) {
            return false;
        }
        if (method_exists($variant, 'stocks') && $variant->stocks()->exists()) {
            return false;
        }

        // Detail transaksi yang memakai varian
        if (method_exists($variant, 'transactionDetails') && $variant->transactionDetails()->exists()) {
            return false;
        }

        // Inventory movements
        if (method_exists($variant, 'inventoryMovements') && $variant->inventoryMovements()->exists()) {
            return false;
        }

        // Baris lain yang mungkin ada (invoiceItems / orderItems)
        if (method_exists($variant, 'invoiceItems') && $variant->invoiceItems()->exists()) {
            return false;
        }
        if (method_exists($variant, 'orderItems') && $variant->orderItems()->exists()) {
            return false;
        }

        return true;
    }
}
