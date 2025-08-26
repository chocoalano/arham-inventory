<?php

namespace App\Policies\Inventory;

use App\Models\Inventory\Product;
use App\Models\User;

class ProductPolicy
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

    /* =========================
     * Core abilities
     * ========================= */

    /** List / Index */
    public function viewAny(User $user): bool
    {
        return $this->perm($user, 'viewAny-product');
    }

    /** View detail */
    public function view(User $user, Product $product): bool
    {
        return $this->perm($user, 'view-product') || $this->isOwner($user, $product);
    }

    /** Create */
    public function create(User $user): bool
    {
        return $this->perm($user, 'create-product');
    }

    /** Update / Edit */
    public function update(User $user, Product $product): bool
    {
        return $this->perm($user, 'update-product') || $this->isOwner($user, $product);
    }

    /** Delete (soft delete) */
    public function delete(User $user, Product $product): bool
    {
        if (! $this->canDeleteProduct($product)) {
            return false;
        }

        return $this->perm($user, 'delete-product');
    }

    /** Bulk delete (soft delete) */
    public function deleteAny(User $user): bool
    {
        return $this->perm($user, 'deleteAny-product');
    }

    /** Restore (from soft delete) */
    public function restore(User $user, Product $product): bool
    {
        return $this->perm($user, 'restore-product');
    }

    /** Bulk restore */
    public function restoreAny(User $user): bool
    {
        return $this->perm($user, 'restoreAny-product');
    }

    /** Force delete (permanent) */
    public function forceDelete(User $user, Product $product): bool
    {
        if (! $this->canDeleteProduct($product)) {
            return false;
        }

        return $this->perm($user, 'forceDelete-product');
    }

    /** Bulk force delete (permanent) */
    public function forceDeleteAny(User $user): bool
    {
        return $this->perm($user, 'forceDeleteAny-product');
    }

    /** Replicate (duplicate record) */
    public function replicate(User $user, Product $product): bool
    {
        return $this->perm($user, 'replicate-product') || $this->isOwner($user, $product);
    }

    /** Export (custom) */
    public function export(User $user): bool
    {
        return $this->perm($user, 'export-product');
    }

    /** Import (custom) */
    public function import(User $user): bool
    {
        return $this->perm($user, 'import-product');
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
     * Owner/penanggung jawab produk (opsional).
     * Aktif jika model punya kolom user_id / created_by.
     */
    private function isOwner(User $user, Product $product): bool
    {
        return (isset($product->user_id) && (int) $product->user_id === (int) $user->id)
            || (isset($product->created_by) && (int) $product->created_by === (int) $user->id);
    }

    /**
     * Cek apakah produk aman dihapus/force delete.
     * - Tidak punya stok/varian/transaksi terkait.
     * Defensif: hanya cek relasi yang tersedia pada model.
     */
    private function canDeleteProduct(Product $product): bool
    {
        // Varian
        if (method_exists($product, 'variants') && $product->variants()->exists()) {
            return false;
        }

        // Stok langsung (jika ada relasi)
        if (method_exists($product, 'stocks') && $product->stocks()->exists()) {
            return false;
        }

        // Detail transaksi yang memakai produk
        if (method_exists($product, 'transactionDetails') && $product->transactionDetails()->exists()) {
            return false;
        }

        // Item invoice / order (opsional sesuai model)
        if (method_exists($product, 'invoiceItems') && $product->invoiceItems()->exists()) {
            return false;
        }
        if (method_exists($product, 'orderItems') && $product->orderItems()->exists()) {
            return false;
        }

        return true;
        }
}
